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

use Pimcore\Model\Site;
use Symfony\Component\DependencyInjection\Container;

class SitesMetrics implements MetricsCollectorInterface
{
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
