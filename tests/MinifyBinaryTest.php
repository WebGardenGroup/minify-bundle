<?php

namespace Wgg\MinifyBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Wgg\MinifyBundle\MinifyBinary;

use function file_exists;
use function file_get_contents;
use function sprintf;
use function str_contains;
use function strtolower;

use const DIRECTORY_SEPARATOR;
use const PHP_OS;

class MinifyBinaryTest extends TestCase
{
    public function testBinaryIsDownloadedAndProcessCreated()
    {
        $binaryDownloadDir = __DIR__.'/fixtures/download';
        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        $os = strtolower(PHP_OS);

        $client = new MockHttpClient([
            new MockResponse(
                match (true) {
                    str_contains($os, 'darwin'), str_contains($os, 'linux') => file_get_contents(__DIR__.'/fixtures/minify.tar.gz'),
                    str_contains($os, 'win') => file_get_contents(__DIR__.'/fixtures/minify.zip'),
                }
            ),
        ]);

        $binary = new MinifyBinary($binaryDownloadDir, __DIR__, null, 'fake-version', null, $client);
        $process = $binary->createProcess(['assets', '-o', 'dist']);
        $this->assertFileExists($binaryDownloadDir.'/fake-version/'.MinifyBinary::getBinaryName());

        // Windows doesn't wrap arguments in quotes
        $expectedTemplate = '\\' === DIRECTORY_SEPARATOR ? '"%s" assets -o dist' : "'%s' 'assets' '-o' 'dist'";

        $this->assertSame(
            sprintf($expectedTemplate, $binaryDownloadDir.'/fake-version/'.MinifyBinary::getBinaryName()),
            $process->getCommandLine()
        );

        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
    }

    public function testCustomBinaryUsed()
    {
        $client = new MockHttpClient();

        $binary = new MinifyBinary('', __DIR__, 'custom-binary', null, null, $client);
        $process = $binary->createProcess(['assets', '-o', 'dist']);
        // on windows, arguments are not wrapped in quotes
        $expected = '\\' === DIRECTORY_SEPARATOR ? 'custom-binary assets -o dist' : "'custom-binary' 'assets' '-o' 'dist'";
        $this->assertSame(
            $expected,
            $process->getCommandLine()
        );
    }
}
