<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Declares set of methods which are required for product price entity
 */
interface ProductPriceInterface
{
    /**
     * @return float
     */
    public function getQuantity();

    /**
     * @return Price
     */
    public function getPrice();

    /**
     * @return MeasureUnitInterface
     */
    public function getUnit();

    /**
     * @return Product
     */
    public function getProduct();
}
