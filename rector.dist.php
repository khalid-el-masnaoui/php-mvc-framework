<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;

return RectorConfig::configure()
    ->withPaths([
        // __DIR__ . '/app',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php81: true)
    ->withPreparedSets(typeDeclarations: true)
    // ->withTypeCoverageLevel(40)
    // ->withPreparedSets(deadCode: true, codeQuality: true, naming: true, privatization: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, privatization: true)
    // ->withDeadCodeLevel(40)
    // ->withSkip([
    //     RenameVariableToMatchMethodCallReturnTypeRector::class => [
    //         __DIR__ . '/tests',
    //     ],
    //     RenameVariableToMatchNewTypeRector::class => [
    //         __DIR__ . '/tests',
    //     ]
    // ])
    ->withCache(
        // ensure file system caching is used instead of in-memory
        cacheClass: FileCacheStorage::class,

        // specify a path that works locally as well as on CI job runners
        cacheDirectory: '/tmp/rector'
    );
