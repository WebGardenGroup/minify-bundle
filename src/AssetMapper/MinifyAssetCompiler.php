<?php

namespace Wgg\MinifyBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Wgg\MinifyBundle\MinifyRunner;

use function preg_replace;

class MinifyAssetCompiler implements AssetCompilerInterface
{
    public function __construct(private readonly MinifyRunner $minify, private readonly bool $convertComments = true)
    {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $this->minify->supportsAssets($asset);
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $asset->addFileDependency($this->minify->getInternalOutputPath($asset->logicalPath));

        if (!$this->convertComments) {
            return $this->minify->getOutputContent($asset->logicalPath);
        }

        return (string) preg_replace('/\/\*!(.+?)\*\//i', '/*$1*/', $this->minify->getOutputContent($asset->logicalPath));
    }
}
