<?php

namespace Wgg\MinifyBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Wgg\MinifyBundle\MinifyRunner;

use function file_exists;
use function file_get_contents;

use const DIRECTORY_SEPARATOR;

class MinifyRunnerTest extends TestCase
{
    protected function setUp(): void
    {
        $fs = new Filesystem();
        if (file_exists(__DIR__.'/fixtures/var/minify')) {
            $fs->remove(__DIR__.'/fixtures/var/minify');
        }
        $fs->mkdir(__DIR__.'/fixtures/var/minify');
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        if (file_exists(__DIR__.'/fixtures/var')) {
            $fs->remove(__DIR__.'/fixtures/var');
        }
    }

    public function testIntegrationWithDefaultOptions(): void
    {
        $builder = new MinifyRunner(
            __DIR__.'/fixtures',
            __DIR__.'/fixtures/var/minify',
            __DIR__.'/fixtures/assets',
            ['js', 'css'],
            [__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'assets/vendor/**'],
        );
        $process = $builder->run(watch: false);
        $process->wait();

        $this->assertTrue($process->isSuccessful());
        $this->assertFileExists(__DIR__.'/fixtures/var/minify/assets/app.js');
        $this->assertFileExists(__DIR__.'/fixtures/var/minify/assets/app.css');
        $this->assertFileDoesNotExist(__DIR__.'/fixtures/var/minify/assets/vendor/excluded.js');

        $this->assertStringContainsString('/*! Comment */const message="Hello world!";console.log(message)', file_get_contents(__DIR__.'/fixtures/var/minify/assets/app.js'), 'The output file should contain minified JS.');
        $this->assertStringContainsString('body{color:red}', file_get_contents(__DIR__.'/fixtures/var/minify/assets/app.css'), 'The output file should contain minified css.');
    }
}
