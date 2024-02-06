<?php

namespace Wgg\MinifyBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Wgg\MinifyBundle\DependencyInjection\MinifyExtension;

class WggMinifyBundle extends Bundle
{
    protected function createContainerExtension(): ?ExtensionInterface
    {
        return new MinifyExtension();
    }
}
