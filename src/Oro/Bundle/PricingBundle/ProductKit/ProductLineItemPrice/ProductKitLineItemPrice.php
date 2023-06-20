<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Represents a product kit line item price.
 */
class ProductKitLineItemPrice extends ProductLineItemPrice
{
    /**
     * @var array<int,ProductKitItemLineItemPrice> Indexed by ProductKitItem::getId()
     */
    protected array $kitItemLineItemPrices = [];

    public function addKitItemLineItemPrice(ProductKitItemLineItemPrice $productKitItemLineItemPrice): self
    {
        $kitItemId = $productKitItemLineItemPrice->getKitItemLineItem()->getKitItem()?->getId();
        if (isset($this->kitItemLineItemPrices[$kitItemId])) {
            throw new \LogicException(
                sprintf(
                    'Product kit item line item price for the %s #%d is already added and cannot be changed',
                    ProductKitItemLineItemInterface::class,
                    $kitItemId
                )
            );
        }

        $this->kitItemLineItemPrices[$kitItemId] = $productKitItemLineItemPrice;

        return $this;
    }

    /**
     * @return array<int,ProductKitItemLineItemPrice>
     */
    public function getKitItemLineItemPrices(): array
    {
        return $this->kitItemLineItemPrices;
    }

    public function getKitItemLineItemPrice(
        ProductKitItemLineItemInterface $kitItemLineItem
    ): ?ProductKitItemLineItemPrice {
        return $this->kitItemLineItemPrices[$kitItemLineItem->getKitItem()?->getId()] ?? null;
    }
}
