<?php

if (!\file_exists(__DIR__.'/src') || !\file_exists(__DIR__.'/tests')) {
    exit(0);
}

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__.'/src', __DIR__.'/tests']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'native_function_invocation' => [
            'include' => ['@internal'],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
