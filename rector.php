<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    // Define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ]);

    // Specific rules
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->rule(ReadOnlyPropertyRector::class);

    // Skip specific files or rules
    $rectorConfig->skip([
        __DIR__.'/vendor',
        __DIR__.'/bootstrap',
        __DIR__.'/storage',
        // Skip readonly property rule for test files that might need mutable properties
        ReadOnlyPropertyRector::class => [
            __DIR__.'/tests',
        ],
    ]);

    // Configure parallel processing
    $rectorConfig->parallel();

    // Import names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);
};
