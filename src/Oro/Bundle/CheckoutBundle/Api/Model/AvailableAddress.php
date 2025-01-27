<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents an available billing or shipping address for Checkout entity.
 */
final readonly class AvailableAddress
{
    public function __construct(
        private string $id,
        private object $address,
        private string $group,
        private string $title
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAddress(): object
    {
        return $this->address;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
