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

namespace CORS\Bundle\PrometheusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cors_prometheus');
        $rootNode = $treeBuilder->getRootNode();

        $supportedTypes = ['in_memory', 'apcu', /*'redis'*/];

        $rootNode
            ->children()
                ->scalarNode('namespace')
                ->isRequired()
                ->cannotBeEmpty()
                ->validate()
                // see: https://github.com/artprima/prometheus-metrics-bundle/issues/32
                    ->ifTrue(function ($s) {
                        return 1 !== preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:]*$/', $s);
                    })
                    ->thenInvalid('Invalid namespace. Make sure it matches the following regex: ^[a-zA-Z_:][a-zA-Z0-9_:]*$')
                    ->end()
                ->end()
            ->scalarNode('type')
                ->validate()
                    ->ifNotInArray($supportedTypes)
                    ->thenInvalid('The type %s is not supported. Please choose one of '.json_encode($supportedTypes))
                ->end()
                ->defaultValue('in_memory')
                ->cannotBeEmpty()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
