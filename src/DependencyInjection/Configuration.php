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

namespace CORS\Bundle\PrometheusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cors_prometheus');
        $rootNode = $treeBuilder->getRootNode();

        $supportedTypes = ['in_memory', 'apcu'/*'redis'*/];

        $rootNode
            ->children()
                ->scalarNode('type')
                    ->validate()
                        ->ifNotInArray($supportedTypes)
                        ->thenInvalid('The type %s is not supported. Please choose one of ' . json_encode($supportedTypes))
                    ->end()
                    ->defaultValue('in_memory')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
