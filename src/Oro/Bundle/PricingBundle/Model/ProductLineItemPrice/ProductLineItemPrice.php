<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Represents a product line item price.
 */
class ProductLineItemPrice
{
    protected ProductLineItemInterface $lineItem;

    protected Price $price;

    protected float $subtotal;

    public function __construct(
        ProductLineItemInterface $lineItem,
        Price $price,
        float $subtotal
    ) {
        $this->lineItem = $lineItem;
        $this->price = $price;
        $this->subtotal = $subtotal;
    }

    public function getLineItem(): ProductLineItemInterface
    {
        return $this->lineItem;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }
}
