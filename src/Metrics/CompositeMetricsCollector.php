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

namespace CORS\Bundle\PrometheusBundle\Metrics;

use CoreShop\Component\Registry\ServiceRegistryInterface;

final class CompositeMetricsCollector implements MetricsCollectorInterface
{
    public function __construct(
        protected ServiceRegistryInterface $collectors,
        protected string $env,
    ) {
    }

    #[\Override]
    public function collect(): array
    {
        $metrics = [];

        /** @var MetricsCollectorInterface $collector */
        foreach ($this->collectors->all() as $collector) {
            foreach ($collector->collect() as $metric) {
                $metrics[] = new Metric(
                    $metric->getName(),
                    array_merge(['env' => $this->env], $metric->getValues()),
                    $metric->getHelp(),
                    $metric->getGaugeValue(),
                );
            }
        }

        return $metrics;
    }
}
