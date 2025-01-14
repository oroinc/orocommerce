<?php

namespace Oro\Bundle\ProductBundle\Api\Model;

/**
 * Represents a product's latest purchase.
 */
final readonly class ProductLatestPurchase
{
    private string $id;

    public function __construct(
        private int $websiteId,
        private int $customerId,
        private int $customerUserId,
        private int $productId,
        private string $unit,
        private string $currency,
        private float $price,
        private \DateTime $purchasedAt
    ) {
        $this->id = sprintf(
            '%d-%d-%d-%d-%s-%s',
            $websiteId,
            $customerId,
            $customerUserId,
            $productId,
            $unit,
            $currency
        );
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getCustomerUserId(): int
    {
        return $this->customerUserId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPurchasedAt(): \DateTime
    {
        return $this->purchasedAt;
    }
}
