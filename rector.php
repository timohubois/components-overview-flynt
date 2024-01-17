<?php

declare(strict_types=1);

use Rector\Set\ValueObject\SetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/classes',
        __DIR__ . '/components-overview-flynt.php',
    ]);

    $rectorConfig->sets([
        SetList::PHP_80
    ]);
};
