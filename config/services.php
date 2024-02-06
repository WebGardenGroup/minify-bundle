<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use Wgg\MinifyBundle\AssetMapper\MinifyAssetCompiler;
use Wgg\MinifyBundle\Command\MinifyRunCommand;
use Wgg\MinifyBundle\MinifyRunner;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('minify.runner', MinifyRunner::class)
            ->args([
                param('kernel.project_dir'),
                param('kernel.project_dir').'/var/minify',
                abstract_arg('path to assets directory'),
                abstract_arg('extensions to minify'),
                abstract_arg('path globs to exclude'),
                abstract_arg('path to Minify binary'),
                abstract_arg('Minify binary version'),
            ])

        ->set('minify.command.run', MinifyRunCommand::class)
            ->args([
                service('minify.runner'),
            ])
            ->tag('console.command')

        ->set('minify.asset_compiler', MinifyAssetCompiler::class)
            ->args([
                service('minify.runner'),
                abstract_arg('convert important comments (/*! ... */) to normal comments'),
            ])
            ->tag('asset_mapper.compiler')
    ;
};
