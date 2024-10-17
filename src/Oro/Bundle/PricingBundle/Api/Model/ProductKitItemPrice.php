<?php

namespace Oro\Bundle\PricingBundle\Api\Model;

/**
 * Represents a product kit's item price.
 */
final readonly class ProductKitItemPrice
{
    private string $id;

    public function __construct(
        private ?int $customerId,
        private int $websiteId,
        private int $productId,
        private string $currency,
        private float $quantity,
        private float $value,
        private string $unit,
        private int $kitItemId
    ) {
        $this->id = implode('-', [
            $kitItemId,
            $customerId ?? CustomerPrice::CUSTOMER_GUEST_FILTER_VALUE,
            $websiteId,
            $productId,
            $currency,
            $unit,
            $quantity
        ]);
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

    public function getKitItemId(): int
    {
        return $this->kitItemId;
    }
}
