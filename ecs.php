<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $ecsConfig->skip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
    ]);

    // PSR-12 standard
    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::COMMON,
        SetList::CLEAN_CODE,
    ]);
};
