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

final class Metric
{
    public function __construct(
        protected string $name,
        protected array $values,
        protected string $help,
        protected float $gaugeValue = 1,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    public function getGaugeValue(): float
    {
        return $this->gaugeValue;
    }
}
