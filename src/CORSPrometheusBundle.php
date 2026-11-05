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

namespace CORS\Bundle\PrometheusBundle;

use CORS\Bundle\PrometheusBundle\DependencyInjection\Compiler\MetricsCollectorPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CORSPrometheusBundle extends AbstractPimcoreBundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MetricsCollectorPass());
    }

    #[\Override]
    public function getNiceName(): string
    {
        return 'CORS - Prometheus Bundle';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'CORS Prometheus Bundle';
    }
}
