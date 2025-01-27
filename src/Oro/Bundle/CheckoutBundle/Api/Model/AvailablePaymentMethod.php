<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents an available payment method for Checkout entity.
 */
final readonly class AvailablePaymentMethod
{
    public function __construct(
        private string $id,
        private string $label,
        private array $options
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
