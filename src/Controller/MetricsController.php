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
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

final class MetricsController
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
