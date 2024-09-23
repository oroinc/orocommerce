<?php

namespace Oro\Bundle\PricingBundle\Api\Model;

/**
 * Represents a customer's price.
 */
final readonly class CustomerPrice
{
    private string $id;

    public function __construct(
        private ?int $customerId,
        private int $websiteId,
        private int $productId,
        private string $currency,
        private float $quantity,
        private float $value,
        private string $unit
    ) {
        $this->id = implode('-', [$customerId ?? 0, $websiteId, $productId, $currency, $unit, $quantity]);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    public function getProductId(): int
    {
        return $this->productId;
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

    public function getUnit(): string
    {
        return $this->unit;
    }
}
