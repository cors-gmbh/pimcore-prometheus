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

use Pimcore\Model\Site;
use Symfony\Component\DependencyInjection\Container;

final class SitesMetrics implements MetricsCollectorInterface
{
    #[\Override]
    public function collect(): array
    {
        $siteListing = new Site\Listing();
        $siteListing->load();

        $metrics = [];

        foreach ($siteListing->getSites() as $site) {
            $metrics[] = new Metric(
                'site_' . str_replace(['.', '-'], '_', Container::underscore($site->getMainDomain())),
                [
                    'type' => 'pimcore_site',
                    'main_domain' => $site->getMainDomain(),
                    'domains' => implode(', ', $site->getDomains()),
                    'redirect_to_main_domain' => $site->getRedirectToMainDomain(),
                    'root_document' => $site->getRootDocument()?->getKey(),
                    'exporter' => 'cors',
                ],
                'Site ' . $site->getMainDomain(),
            );
        }

        return $metrics;
    }
}
