<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Represents a product kit item line item price.
 */
class ProductKitItemLineItemPrice extends ProductLineItemPrice
{
    protected ProductKitItemLineItemInterface $kitItemLineItem;

    public function __construct(ProductKitItemLineItemInterface $kitItemLineItem, Price $price, float $subtotal)
    {
        parent::__construct($kitItemLineItem, $price, $subtotal);

        $this->kitItemLineItem = $kitItemLineItem;
    }

    public function getKitItemLineItem(): ProductKitItemLineItemInterface
    {
        return $this->kitItemLineItem;
    }
}
