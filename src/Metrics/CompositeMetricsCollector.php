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

use CoreShop\Component\Registry\ServiceRegistryInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricNotFoundException;

final class CompositeMetricsCollector implements MetricsCollectorInterface
{
    public function __construct(
        protected ServiceRegistryInterface $collectors,
        protected string $namespace,
        protected CollectorRegistry $collectorRegistry
    )
    {

    }

    public function collect(): array
    {
        foreach ($this->collectors->all() as $collector) {
            $metrics = $collector->collect();

            /**
             * @var Metric[] $metrics
             */
            foreach ($metrics as $metric) {
                try {
                    // the trick with try/catch lets us setting the instance name only once
                    $this->collectorRegistry->getGauge('cors_pimcore', $metric->getName());
                } catch (MetricNotFoundException $e) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $gauge = $this->collectorRegistry->registerGauge(
                        'cors_pimcore',
                        $metric->getName(),
                        $metric->getHelp(),
                        array_merge(['namespace'], array_keys($metric->getValues()))
                    );
                    $gauge->set($metric->getGaugeValue(), array_merge([$this->namespace], array_values($metric->getValues())));
                }
            }
        }

        return [];
    }
}
