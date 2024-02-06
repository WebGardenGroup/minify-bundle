<?php

namespace Wgg\MinifyBundle\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Wgg\MinifyBundle\AssetMapper\MinifyAssetCompiler;
use Wgg\MinifyBundle\MinifyRunner;

class MinifyAssetCompilerTest extends TestCase
{
    public function testCompile()
    {
        $builder = $this->createMock(MinifyRunner::class);
        $builder->expects($this->exactly(2))
            ->method('getInternalOutputPath');
        $builder->expects($this->exactly(2))
            ->method('supportsAssets')
            ->willReturnCallback(static fn (MappedAsset $asset): bool => 'excluded.js' !== $asset->logicalPath);
        $builder->expects($this->exactly(2))
            ->method('getOutputContent')
            ->willReturn('/*! comment */ output content from Minify');

        $compiler = new MinifyAssetCompiler($builder, true);
        $asset1 = new MappedAsset('excluded.js', __DIR__.'/../fixtures/assets/vendor/excluded.js');
        $asset2 = new MappedAsset('app.js', __DIR__.'/../fixtures/assets/app.js');
        $this->assertFalse($compiler->supports($asset1));
        $this->assertTrue($compiler->supports($asset2));

        $this->assertSame('/* comment */ output content from Minify', $compiler->compile('input content', $asset2, $this->createMock(AssetMapperInterface::class)));

        $compiler = new MinifyAssetCompiler($builder, false);

        $this->assertSame('/*! comment */ output content from Minify', $compiler->compile('input content', $asset2, $this->createMock(AssetMapperInterface::class)));
    }
}
