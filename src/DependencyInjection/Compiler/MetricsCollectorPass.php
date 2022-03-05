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

namespace CORS\Bundle\PrometheusBundle\DependencyInjection\Compiler;

use CoreShop\Component\Registry\RegisterSimpleRegistryTypePass;

final class MetricsCollectorPass extends RegisterSimpleRegistryTypePass
{
    public const TAG = 'cors.metrics_collector';

    public function __construct()
    {
        parent::__construct(
            'cors.registry.metrics_collector',
            'cors.metrics_collectors',
            self::TAG
        );
    }
}
