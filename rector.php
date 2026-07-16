<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/ext_emconf.php',
    ]);
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);
    $rectorConfig->skip([
        // ActionController hat mutable Properties, kann nicht readonly sein
        \Rector\Php82\Rector\Class_\ReadOnlyClassRector::class => [
            __DIR__ . '/Classes/Controller/*',
        ],
    ]);
};
