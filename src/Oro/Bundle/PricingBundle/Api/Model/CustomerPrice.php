<?php

namespace Oro\Bundle\PricingBundle\Api\Model;

/**
 * Represents a customer's price {@link ProductPrice} by scope criteria.
 */
class CustomerPrice
{
    public function __construct(
        private ?string $id,
        private string $currency,
        private float $quantity,
        private float $value,
        private int $productId,
        private string $unit,
        private ?int $customerId,
        private ?int $websiteId,
    ) {
        $this->id ??= $this->buildCustomerPriceId();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getWebsiteId(): ?int
    {
        return $this->websiteId;
    }

    public function buildCustomerPriceId(): string
    {
        return implode('-', array_filter([
            $this->customerId,
            $this->websiteId,
            $this->productId,
            $this->currency,
            $this->unit,
            $this->quantity
        ]));
    }
}
