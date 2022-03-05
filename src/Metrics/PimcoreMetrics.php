<?php

declare(strict_types=1);

/**
 * CORS GmbH.
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    https://www.cors.gmbh/license     GPLv3 and PCL
 */

namespace CORS\Bundle\PrometheusBundle\Metrics;

use Doctrine\DBAL\Connection;
use Pimcore\Version;

class PimcoreMetrics implements MetricsCollectorInterface
{
    public function __construct(protected Connection $connection)
    {
    }

    public function collect(): array
    {
        try {
            $tables = $this->connection->fetchAll('SELECT TABLE_NAME as name,TABLE_ROWS as `rows` from information_schema.TABLES
                WHERE TABLE_ROWS IS NOT NULL AND TABLE_SCHEMA = ?', [$this->connection->getDatabase()]);
        } catch (\Exception $e) {
            $tables = [];
        }

        try {
            $mysqlVersion = $this->connection->fetchOne('SELECT VERSION()');
        } catch (\Exception $e) {
            $mysqlVersion = null;
        }

        $metrics = [
            new Metric('pimcore_version', ['type' => 'pimcore_version', 'version' => Version::getVersion()], 'Version of Pimcore'),
            new Metric('mysql_version', ['type' => 'pimcore_mysql_version', 'version' => $mysqlVersion], 'Version of MySQL'),
        ];

        foreach ($tables as $table) {
            $metrics[] = new Metric('table_'.$table['name'], ['type' => 'pimcore_tables'], 'Table of Pimcore', (int) $table['rows']);
        }

        return $metrics;
    }
}
