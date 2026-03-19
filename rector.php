<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
    ]);
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ]);
};
