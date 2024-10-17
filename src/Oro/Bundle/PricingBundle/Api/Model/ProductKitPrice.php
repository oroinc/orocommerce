<?php

namespace Oro\Bundle\PricingBundle\Api\Model;

/**
 * Represents a product kit's price.
 */
final class ProductKitPrice
{
    private readonly string $id;

    /** @param array<int,ProductKitItemPrice> $kitItemPrices */
    public function __construct(
        private readonly ?int $customerId,
        private readonly int $websiteId,
        private readonly int $productId,
        private readonly string $currency,
        private readonly float $quantity,
        private readonly float $value,
        private readonly string $unit,
        private array $kitItemPrices = []
    ) {
        $this->id = implode('-', [
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

    /** @return array<int,ProductKitItemPrice> */
    public function getKitItemPrices(): array
    {
        return $this->kitItemPrices;
    }

    public function addKitItemPrice(ProductKitItemPrice $kitItemPrice): self
    {
        $this->kitItemPrices[$kitItemPrice->getId()] = $kitItemPrice;

        return $this;
    }
}
