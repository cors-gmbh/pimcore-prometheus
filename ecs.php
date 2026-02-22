<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withSets([
        __DIR__ . '/vendor/cors/dev/ecs.php',
    ])
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withParallel()
;
