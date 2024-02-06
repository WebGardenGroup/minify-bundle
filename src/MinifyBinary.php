<?php

namespace Wgg\MinifyBundle;

use Exception;
use PharData;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use ZipArchive;

use function array_unshift;
use function assert;
use function chmod;
use function count;
use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function is_file;
use function is_resource;
use function mkdir;
use function php_uname;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function strtolower;
use function unlink;

use const PHP_OS;

final class MinifyBinary
{
    private readonly HttpClientInterface $httpClient;
    private ?string $cachedVersion = null;

    public function __construct(
        private readonly string $binaryDownloadDir,
        private readonly string $cwd,
        private readonly ?string $binaryPath,
        private readonly ?string $binaryVersion,
        private readonly ?SymfonyStyle $output = null,
        ?HttpClientInterface $httpClient = null,
        private readonly bool $useExistingVersionOnError = true
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * @param array<mixed> $arguments
     */
    public function createProcess(array $arguments = []): Process
    {
        if (null === $this->binaryPath) {
            $binary = $this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName();
            if (!is_file($binary)) {
                $this->downloadExecutable();
            }
        } else {
            $binary = $this->binaryPath;
        }

        // add $binary to the front of the $arguments array
        array_unshift($arguments, $binary);

        return new Process($arguments, $this->cwd);
    }

    public function downloadExecutable(): void
    {
        $targetPathArchive = $this->binaryDownloadDir.'/'.self::getDownloadName();

        $this->downloadArchive($targetPathArchive);

        $this->extractArchive($targetPathArchive);
    }

    public static function getBinaryName(): string
    {
        $os = strtolower(PHP_OS);
        $machine = strtolower(php_uname('m'));

        if (str_contains($os, 'darwin') || str_contains($os, 'linux')) {
            return 'minify';
        }

        if (str_contains($os, 'win')) {
            return 'minify.exe';
        }

        throw new Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }

    private static function getDownloadName(): string
    {
        $os = strtolower(PHP_OS);
        $machine = strtolower(php_uname('m'));

        if (str_contains($os, 'darwin')) {
            if ('arm64' === $machine) {
                return 'minify_darwin_arm64.tar.gz';
            }
            if ('x86_64' === $machine) {
                return 'minify_darwin_md64.tar.gz';
            }

            throw new Exception(sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'linux')) {
            if ('arm64' === $machine || 'arch64' === $machine) {
                return 'minify_linux_arm64.tar.gz';
            }
            if ('x86_64' === $machine) {
                return 'minify_linux_amd64.tar.gz';
            }

            throw new Exception(sprintf('No matching machine found for Linux platform (Machine: %s).', $machine));
        }

        if (str_contains($os, 'win')) {
            if ('x86_64' === $machine || 'amd64' === $machine) {
                return 'minify_windows_amd64.zip';
            }

            throw new Exception(sprintf('No matching machine found for Windows platform (Machine: %s).', $machine));
        }

        throw new Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine));
    }

    private function downloadArchive(string $targetPathArchive): void
    {
        $url = sprintf('https://github.com/tdewolff/minify/releases/download/%s/%s', $this->getVersion(), self::getDownloadName());

        $this->output?->note(sprintf('Downloading Minify binary from %s', $url));

        if (!is_dir($this->binaryDownloadDir)) {
            mkdir($this->binaryDownloadDir, 0777, true);
        }
        $progressBar = null;

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                // dlSize is not known at the start
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output?->createProgressBar($dlSize);
                }

                $progressBar?->setProgress($dlNow);
            },
        ]);

        $fileHandler = fopen($targetPathArchive, 'w');
        assert(is_resource($fileHandler));
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);
        $progressBar?->finish();
        $this->output?->writeln('');
    }

    private function getVersion(): string
    {
        try {
            return $this->cachedVersion ??= $this->binaryVersion ?? $this->getLatestVersion();
        } catch (Throwable $e) {
            if ($this->useExistingVersionOnError) {
                return $this->cachedVersion ??= $this->getExistingVersion();
            }
            throw $e;
        }
    }

    private function getLatestVersion(): string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.github.com/repos/tdewolff/minify/releases/latest');

            return $response->toArray()['name'] ?? throw new Exception('Cannot get the latest version name from response JSON.');
        } catch (Throwable $e) {
            throw new Exception('Cannot determine latest Minify binary version. Please specify a version in the configuration.', previous: $e);
        }
    }

    private function extractArchive(string $targetPathArchive): void
    {
        if (str_ends_with($targetPathArchive, '.tar.gz')) {
            $tgz = new PharData($targetPathArchive);
            $tgz->extractTo($this->binaryDownloadDir.'/'.$this->getVersion().'/', self::getBinaryName(), true);
        } elseif (str_ends_with($targetPathArchive, '.zip')) {
            $zip = new ZipArchive();
            $zip->open($targetPathArchive);
            $zip->extractTo($this->binaryDownloadDir.'/'.$this->getVersion().'/', self::getBinaryName());
        }

        @unlink($targetPathArchive);

        // make file executable
        chmod($this->binaryDownloadDir.'/'.$this->getVersion().'/'.self::getBinaryName(), 0777);
    }

    private function getExistingVersion(): string
    {
        $finder = Finder::create()
            ->in($this->binaryDownloadDir)
            ->name(self::getBinaryName())
            ->sortByName()
            ->reverseSorting()
            ->files();

        if (!count($finder)) {
            throw new Exception('No version is downloaded yet');
        }

        return $finder->getIterator()->current()->getRelativePath();
    }
}
