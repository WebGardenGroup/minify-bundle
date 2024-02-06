<?php

namespace Wgg\MinifyBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Filesystem;

use function assert;
use function file_put_contents;
use function is_dir;

class FunctionalTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $fs = new Filesystem();
        $minifyVarDir = __DIR__.'/fixtures/var/minify';
        if (is_dir($minifyVarDir)) {
            $fs->remove($minifyVarDir);
        }
        $fs->mkdir($minifyVarDir.'/assets');
        file_put_contents($minifyVarDir.'/assets/app.js', <<<EOF
        Minified content
        EOF
        );
    }

    protected function tearDown(): void
    {
        $fs = new Filesystem();
        $minifyVarDir = __DIR__.'/fixtures/var';
        if (is_dir($minifyVarDir)) {
            $fs->remove($minifyVarDir);
        }
    }

    public function testMinifiedFileIsUsed(): void
    {
        self::bootKernel();
        $assetMapper = self::getContainer()->get('asset_mapper');
        assert($assetMapper instanceof AssetMapperInterface);

        $asset = $assetMapper->getAsset('app.js');

        $this->assertInstanceOf(MappedAsset::class, $asset);
        $this->assertStringContainsString('Minified content', $asset->content);
    }
}
