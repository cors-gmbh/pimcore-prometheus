<?php

declare(strict_types=1);

/*
 * CORS GmbH
 *
 * This source file is available under the MIT license
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    https://opensource.org/license/mit MIT
 *
 */

namespace CORS\Bundle\PrometheusBundle;

use Prometheus\Storage\Redis;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

final class StorageFactory
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
