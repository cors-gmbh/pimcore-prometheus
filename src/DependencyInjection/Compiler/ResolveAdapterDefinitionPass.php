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

namespace CORS\Bundle\PrometheusBundle\DependencyInjection\Compiler;

use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResolveAdapterDefinitionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(Adapter::class)) {
            return;
        }

        $adapterClasses = [
            'in_memory' => InMemory::class,
            'apcu' => APC::class,
            //'redis' => Redis::class,
        ];

        $type = (string) $container->getParameter('cors_prometheus.type');

        if (!isset($adapterClasses[$type])) {
            throw new \InvalidArgumentException('Invalid cors_prometheus.type value.');
        }

        $definition = $container->getDefinition(Adapter::class);
        $definition->setAbstract(false);
        $definition->setClass($adapterClasses[$type]);
    }
}
