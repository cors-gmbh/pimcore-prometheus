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

namespace CORS\Bundle\PrometheusBundle\Messenger;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Throwable;

class PrometheusMiddleware implements MiddlewareInterface
{
    private CollectorRegistry $collectorRegistry;

    private string $metricName;

    private string $helpText;

    private string $errorHelpText;

    /**
     * @var array|string[]
     */
    private array $labels;

    /**
     * @var array|string[]
     */
    private array $errorLabels;

    public function __construct(
        CollectorRegistry $collectorRegistry,
        string $metricName = 'message',
        string $helpText = null,
        array $labels = null,
        string $errorHelpText = null,
        array $errorLabels = null,
    ) {
        $this->collectorRegistry = $collectorRegistry;
        $this->helpText = $helpText ?? 'Executed Messages';
        $this->labels = $labels ?? ['message', 'label'];
        $this->metricName = $metricName;
        $this->errorHelpText = $errorHelpText ?? 'Failed Messages';
        $this->errorLabels = $errorLabels ?? ['message', 'label'];
    }

    /**
     * @throws Throwable
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (null === $envelope->last(ReceivedStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        if (null !== $envelope->last(PrometheusStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $envelope = $envelope->with(new PrometheusStamp());

        $busName = 'default_messenger';

        /** @var BusNameStamp|null $stamp */
        $stamp = $envelope->last(BusNameStamp::class);

        if (true === $stamp instanceof BusNameStamp) {
            $busName = str_replace(['.', '-'], '_', $stamp->getBusName());
        }

        $counter = $this->getCounter(
            $busName,
            $this->metricName,
            $this->helpText,
            $this->labels,
        );

        $values = $this->labelValueProvider($envelope, $stack);

        try {
            $counter->inc($values);

            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (Throwable $exception) {
            $counter = $this->getCounter(
                $busName,
                $this->metricName . '_error',
                $this->errorHelpText,
                $this->errorLabels,
            );

            $errorValues = $this->errorLabelValueProvider($envelope, $stack, $exception);

            $counter->inc($errorValues);

            throw $exception;
        }

        return $envelope;
    }

    private function labelValueProvider(Envelope $envelope, StackInterface $stack): array
    {
        $message = $envelope->getMessage();

        return [
            \get_class($message),
            substr((string) strrchr(get_class($message), '\\'), 1),
        ];
    }

    private function errorLabelValueProvider(Envelope $envelope, StackInterface $stack, Throwable $exception): array
    {
        $message = $envelope->getMessage();

        return [
            \get_class($message),
            substr((string) strrchr(get_class($message), '\\'), 1),
        ];
    }

    /**
     * @param array<string> $labels
     */
    private function getCounter(string $busName, string $name, string $helperText, array $labels = []): Counter
    {
        return $this->collectorRegistry->getOrRegisterCounter(
            $busName,
            $name,
            $helperText,
            $labels,
        );
    }
}
