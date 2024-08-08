<?php

namespace Wgg\MinifyBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class WggMinifyBundle extends AbstractBundle
{
    protected string $extensionAlias = 'wgg_minify';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $container->services()->get('minify.runner')
            ->arg('$assetsDir', $config['assets_directory'])
            ->arg('$extensions', $config['extensions'])
            ->arg('$excludedPaths', $config['excluded_paths'])
            ->arg('$binaryPath', $config['binary'])
            ->arg('$binaryVersion', $config['binary_version']);

        $container->services()->get('minify.asset_compiler')
            ->arg('$convertComments', $config['convert_comments']);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('assets_directory')
                    ->info('Assets directory to minify - relative to project root dir')
                    ->defaultValue('assets')
                ->end()
                ->arrayNode('extensions')
                    ->info('Extensions to minify')
                    ->cannotBeEmpty()
                    ->scalarProtoType()
                        ->end()
                    ->defaultValue(['js', 'css'])
                ->end()
                ->arrayNode('excluded_paths')
                    ->info('Paths to exclude from minification - relative to project root dir')
                    ->scalarProtoType()
                        ->example('assets/do-not-minify/**')
                        ->end()
                    ->defaultValue(['assets/vendor/**'])
                ->end()
                ->booleanNode('convert_comments')
                    ->info('Convert important comments (/*! ... */) back to normal')
                    ->defaultTrue()
                ->end()
                ->scalarNode('binary')
                    ->info('The Minify binary to use instead of downloading a new one')
                    ->defaultNull()
                ->end()
                ->scalarNode('binary_version')
                    ->info('Minify version to download - null means the latest version')
                    ->defaultNull()
                ->end()
            ->end();
    }
}
