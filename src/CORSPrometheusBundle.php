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

namespace CORS\Bundle\PrometheusBundle;

use CORS\Bundle\PrometheusBundle\DependencyInjection\Compiler\MetricsCollectorPass;
use CORS\Bundle\PrometheusBundle\DependencyInjection\Compiler\ResolveAdapterDefinitionPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CORSPrometheusBundle extends AbstractPimcoreBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ResolveAdapterDefinitionPass());
        $container->addCompilerPass(new MetricsCollectorPass());
    }

    public function getNiceName(): string
    {
        return 'CORS - Prometheus Bundle';
    }

    public function getDescription(): string
    {
        return 'CORS Prometheus Bundle';
    }
}
