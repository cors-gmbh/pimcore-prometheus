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
