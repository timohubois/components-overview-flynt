<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->autoloadPaths([
        __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php',
    ]);

    $rectorConfig->paths([
        __DIR__ . '/',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
    ]);

    $rectorConfig->sets([
        SetList::PHP_80,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::NAMING,
        SetList::TYPE_DECLARATION
    ]);

    $rectorConfig->skip([
        CallableThisArrayToAnonymousFunctionRector::class,
    ]);

    $rectorConfig->rule(RemoveMethodCallParamRector::class);
    $rectorConfig->rule(ReplaceArgumentDefaultValueRector::class);
};
