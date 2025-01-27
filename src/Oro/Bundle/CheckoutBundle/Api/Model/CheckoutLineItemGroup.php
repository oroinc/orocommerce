<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents a group of checkout line items.
 */
final class CheckoutLineItemGroup
{
    private ?string $name = null;
    private ?int $itemCount = null;
    private ?float $totalValue = null;
    private ?string $currency = null;
    private ?string $shippingMethod = null;
    private ?string $shippingMethodType = null;
    private ?float $shippingEstimateAmount = null;

    public function __construct(
        private readonly string $id,
        private readonly int $checkoutId,
        private readonly string $groupKey
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCheckoutId(): int
    {
        return $this->checkoutId;
    }

    public function getGroupKey(): string
    {
        return $this->groupKey;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getItemCount(): ?int
    {
        return $this->itemCount;
    }

    public function setItemCount(?int $itemCount): void
    {
        $this->itemCount = $itemCount;
    }

    public function getTotalValue(): ?float
    {
        return $this->totalValue;
    }

    public function setTotalValue(?float $totalValue): void
    {
        $this->totalValue = $totalValue;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?string $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getShippingMethodType(): ?string
    {
        return $this->shippingMethodType;
    }

    public function setShippingMethodType(?string $shippingMethodType): void
    {
        $this->shippingMethodType = $shippingMethodType;
    }

    public function getShippingEstimateAmount(): ?float
    {
        return $this->shippingEstimateAmount;
    }

    public function setShippingEstimateAmount(?float $shippingEstimateAmount): void
    {
        $this->shippingEstimateAmount = $shippingEstimateAmount;
    }
}
