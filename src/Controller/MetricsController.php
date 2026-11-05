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

namespace CORS\Bundle\PrometheusBundle\Controller;

use CORS\Bundle\PrometheusBundle\Metrics\MetricsCollectorInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\MetricFamilySamples;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Symfony\Component\HttpFoundation\Response;

final class MetricsController
{
    public const NAMESPACE = 'cors_pimcore';

    /**
     * The collector metrics (bundles, sites, tables, versions) are a snapshot taken on every scrape.
     * They live in a registry of their own for this request only: written into the configured storage,
     * a series whose labels changed (a bundle update, a removed table) would stay there forever.
     * The configured storage only holds what has to survive the request, like the Messenger counters.
     */
    public function prometheus(
        CollectorRegistry $collectorRegistry,
        MetricsCollectorInterface $collector,
    ): Response {
        $snapshot = new CollectorRegistry(new InMemory(), false);

        foreach ($collector->collect() as $metric) {
            try {
                // the first metric of a name wins, as the labels of a gauge are fixed on registration
                $snapshot->getGauge(self::NAMESPACE, $metric->getName());

                continue;
            } catch (MetricNotFoundException) {
            }

            $values = $metric->getValues();

            /** @noinspection PhpUnhandledExceptionInspection */
            $snapshot
                ->registerGauge(self::NAMESPACE, $metric->getName(), $metric->getHelp(), array_keys($values))
                ->set($metric->getGaugeValue(), array_values($values))
            ;
        }

        $samples = array_merge(
            $this->withoutCollectorGauges($collectorRegistry->getMetricFamilySamples()),
            $snapshot->getMetricFamilySamples(),
        );

        usort(
            $samples,
            static fn (MetricFamilySamples $a, MetricFamilySamples $b): int => strcmp($a->getName(), $b->getName()),
        );

        return new Response((new RenderTextFormat())->render($samples), 200, [
            'Content-type' => RenderTextFormat::MIME_TYPE,
        ]);
    }

    /**
     * Earlier versions wrote the collector gauges into the configured storage; a persistent one (Redis)
     * still holds them and would report every outdated series next to the current snapshot.
     *
     * @param MetricFamilySamples[] $samples
     *
     * @return MetricFamilySamples[]
     */
    private function withoutCollectorGauges(array $samples): array
    {
        return array_values(array_filter(
            $samples,
            static fn (MetricFamilySamples $family): bool => 'gauge' !== $family->getType() ||
                !str_starts_with($family->getName(), self::NAMESPACE . '_'),
        ));
    }
}
