<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/lang',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    // Enable PHP 8.3 features and transformations
    ->withPhpSets()

    // Laravel-specific refactoring rules (auto-detects version from composer.json)
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)

    // Enable prepared sets for code quality improvements
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true
    )

    // Skip rules that conflict with Pint or Laravel conventions
    ->withSkip([
        // Skip cache and generated files
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',

        // Skip rules that conflict with Laravel coding style
        \Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class => [
            __DIR__ . '/config',
            __DIR__ . '/database',
        ],

        // Skip routes files from certain transformations (preserve Laravel route syntax)
        \Rector\CodeQuality\Rector\Closure\StaticClosureRector::class => [
            __DIR__ . '/routes',
        ],

        // Preserve PHPDoc for IDE support and static analysis
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector::class => [
            __DIR__ . '/config',
        ],
    ]);
