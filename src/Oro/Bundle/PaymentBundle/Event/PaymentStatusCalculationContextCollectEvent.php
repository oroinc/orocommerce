<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to collect context data for payment status calculation.
 * This event is dispatched at the beginning of the payment status calculation process.
 */
class PaymentStatusCalculationContextCollectEvent extends Event
{
    private array $contextData = [];

    public function __construct(private readonly object $entity)
    {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }

    public function setContextData(array $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function getContextItem(string $name): mixed
    {
        return $this->contextData[$name] ?? null;
    }

    public function setContextItem(string $name, mixed $value): self
    {
        $this->contextData[$name] = $value;

        return $this;
    }
}
