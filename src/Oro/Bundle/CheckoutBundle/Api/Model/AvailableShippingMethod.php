<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents an available shipping method for Checkout entity.
 */
final readonly class AvailableShippingMethod
{
    public function __construct(
        private string $id,
        private string $label,
        private array $types
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

    /**
     * @return AvailableShippingMethodType[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }
}
