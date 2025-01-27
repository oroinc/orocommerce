<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents an available shipping method type for Checkout entity.
 */
final readonly class AvailableShippingMethodType
{
    public function __construct(
        private string $id,
        private string $label,
        private float $shippingCost,
        private string $currency
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

    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}
