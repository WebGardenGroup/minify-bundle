<?php

namespace Wgg\MinifyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function assert;

class MinifyExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition('minify.runner')
            ->replaceArgument(2, $config['assets_directory'])
            ->replaceArgument(3, $config['extensions'])
            ->replaceArgument(4, $config['excluded_paths'])
            ->replaceArgument(5, $config['binary'])
            ->replaceArgument(6, $config['binary_version']);

        $container->getDefinition('minify.asset_compiler')
            ->replaceArgument(1, $config['convert_comments']);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return $this;
    }

    public function getAlias(): string
    {
        return 'wgg_minify';
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wgg_minify');
        $rootNode = $treeBuilder->getRootNode();
        assert($rootNode instanceof ArrayNodeDefinition);

        $rootNode
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

        return $treeBuilder;
    }
}
