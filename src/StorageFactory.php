<?php

declare(strict_types=1);

/*
 * CORS GmbH
 *
 * This software is available under the GNU General Public License version 3 (GPLv3).
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    https://www.cors.gmbh/license GPLv3
 */

namespace CORS\Bundle\PrometheusBundle;

use Prometheus\Storage\Redis;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class StorageFactory
{
    public static function create(string $dsn): Redis
    {
        if (!str_starts_with($dsn, 'redis:')) {
            throw new \InvalidArgumentException(sprintf('Unsupported DSN "%s". Only Redis is supported', $dsn));
        }

        $connection = AbstractAdapter::createConnection($dsn, ['lazy' => false]);

        Redis::setPrefix('prom_');

        /**
         * @phpstan-ignore-next-line
         */
        return Redis::fromExistingConnection($connection);
    }
}
