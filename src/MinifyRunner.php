<?php

namespace Wgg\MinifyBundle;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

use function basename;
use function file_get_contents;
use function in_array;
use function is_dir;
use function is_file;
use function preg_match;
use function realpath;
use function sprintf;
use function str_starts_with;

class MinifyRunner
{
    private ?SymfonyStyle $output = null;

    /**
     * @param string[] $excludedPaths
     * @param string[] $extensions
     */
    public function __construct(
        private readonly string $projectRootDir,
        private readonly string $minifyVarDir,
        private readonly string $assetsDir,
        private readonly array $extensions,
        private readonly array $excludedPaths,
        private readonly ?string $binaryPath = null,
        private readonly ?string $binaryVersion = null
    ) {
        if (!is_dir($this->projectRootDir.'/'.$this->assetsDir)) {
            throw new InvalidArgumentException(sprintf('The input directory "%s" does not exist.', $this->projectRootDir.'/'.$this->assetsDir));
        }
    }

    public function run(bool $watch = false): Process
    {
        $binary = new MinifyBinary($this->minifyVarDir, $this->projectRootDir, $this->binaryPath, $this->binaryVersion, $this->output);
        $arguments = $this->prepareProcessArguments($watch);

        return $this->startProcess($binary->createProcess($arguments), $watch);
    }

    public function setOutputStyle(SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    public function getInternalOutputPath(string $logicalPath): string
    {
        return $this->minifyVarDir.'/'.basename($this->assetsDir).'/'.$logicalPath;
    }

    public function getOutputContent(string $logicalPath): string
    {
        $filePath = $this->getInternalOutputPath($logicalPath);
        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Minified file (%s) does not exist: run "php bin/console minify:run" to generate it', $filePath));
        }

        return file_get_contents($filePath) ?: throw new RuntimeException('Minified file is not readable / accessible');
    }

    public function supportsAssets(MappedAsset $asset): bool
    {
        return !$asset->isVendor
            && str_starts_with(realpath($asset->sourcePath), realpath($this->assetsDir))
            && in_array($asset->publicExtension, $this->extensions)
            && !$this->isExcluded($asset->sourcePath);
    }

    private function isExcluded(string $path): bool
    {
        foreach ($this->excludedPaths as $glob) {
            if (preg_match(Glob::toRegex($glob, true, false), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function prepareProcessArguments(bool $watch): array
    {
        $arguments = [
            $this->assetsDir,
            '--recursive',
            '--output='.$this->minifyVarDir,
        ];

        if ($watch) {
            $arguments[] = '--watch';
        }

        foreach ($this->excludedPaths as $exclude) {
            $arguments[] = '--exclude='.$exclude;
        }

        foreach ($this->extensions as $extension) {
            $arguments[] = '--match=**.'.$extension;
        }

        return $arguments;
    }

    private function startProcess(Process $process, bool $watch): Process
    {
        if ($watch) {
            $process->setTimeout(null);

            $inputStream = new InputStream();
            $process->setInput($inputStream);
        }

        $this->output?->note('Executing Minify (pass -v to see more details).');
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    '.$process->getCommandLine(),
            ]);
        }
        $process->start();

        return $process;
    }
}
