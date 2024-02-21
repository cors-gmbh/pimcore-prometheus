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

class Metric
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

    public function getGaugeValue(): float|int
    {
        return $this->gaugeValue;
    }
}
