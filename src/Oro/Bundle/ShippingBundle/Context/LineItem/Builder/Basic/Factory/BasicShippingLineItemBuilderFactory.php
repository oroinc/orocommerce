<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;

class BasicShippingLineItemBuilderFactory implements ShippingLineItemBuilderFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createBuilder(
        Price $price,
        ProductUnit $productUnit,
        $productUnitCode,
        $quantity,
        ProductHolderInterface $productHolder
    ) {
        return new BasicShippingLineItemBuilder($price, $productUnit, $productUnitCode, $quantity, $productHolder);
    }
}
