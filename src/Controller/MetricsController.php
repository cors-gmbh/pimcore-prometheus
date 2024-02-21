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

namespace CORS\Bundle\PrometheusBundle\Controller;

use CORS\Bundle\PrometheusBundle\Metrics\MetricsCollectorInterface;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class MetricsController
{
    public function prometheus(
        CollectorRegistry $collectionRegistry,
        MetricsCollectorInterface $collector,
    ): Response {
        $collector->collect();

        $response = (new RenderTextFormat())->render($collectionRegistry->getMetricFamilySamples());

        return new Response($response, 200, ['Content-type' => RenderTextFormat::MIME_TYPE]);
    }
}
