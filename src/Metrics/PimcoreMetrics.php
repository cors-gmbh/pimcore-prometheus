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

namespace CORS\Bundle\PrometheusBundle\Metrics;

use Doctrine\DBAL\Connection;
use Pimcore\Version;

final class PimcoreMetrics implements MetricsCollectorInterface
{
    public function __construct(
        protected Connection $connection,
    ) {
    }

    #[\Override]
    public function collect(): array
    {
        try {
            $tables = $this->connection->fetchAllAssociative('SELECT TABLE_NAME as name,TABLE_ROWS as `rows` from information_schema.TABLES
                WHERE TABLE_ROWS IS NOT NULL AND TABLE_SCHEMA = ?', [$this->connection->getDatabase()]);
        } catch (\Exception $e) {
            $tables = [];
        }

        try {
            $mysqlVersion = $this->connection->fetchOne('SELECT VERSION()');
        } catch (\Exception $e) {
            $mysqlVersion = null;
        }

        /**
         * @psalm-suppress InternalClass
         * @psalm-suppress InternalMethod
         * **/
        $metrics = [
            new Metric('pimcore_version', ['exporter' => 'cors', 'type' => 'pimcore_version', 'version' => Version::getVersion()], 'Version of Pimcore'),
            new Metric('mysql_version', ['exporter' => 'cors', 'type' => 'pimcore_mysql_version', 'version' => $mysqlVersion], 'Version of MySQL'),
        ];

        foreach ($tables as $table) {
            /**
             * @var string $name
             */
            $name = $table['name'];
            /**
             * @var string $rows
             */
            $rows = $table['rows'];
            $metrics[] = new Metric('table_' . $name, ['exporter' => 'cors', 'type' => 'pimcore_tables'], 'Table of Pimcore', (int) $rows);
        }

        return $metrics;
    }
}
