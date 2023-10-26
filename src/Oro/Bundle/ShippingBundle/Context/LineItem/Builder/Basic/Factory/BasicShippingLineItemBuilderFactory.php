<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

/**
 * Basic implementation of the shipping line item model builder factory.
 *
 * @deprecated since 5.1
 */
class BasicShippingLineItemBuilderFactory implements ShippingLineItemBuilderFactoryInterface
{
    private ?Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface
        $shippingKitItemLineItemFactory = null;

    public function setShippingKitItemLineItemFactory(
        ?Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface $shippingKitItemLineItemFactory
    ): void {
        $this->shippingKitItemLineItemFactory = $shippingKitItemLineItemFactory;
    }

    public function createBuilder(
        ProductUnit $productUnit,
        $productUnitCode,
        $quantity,
        ProductHolderInterface $productHolder
    ) {
        $builder = new BasicShippingLineItemBuilder($productUnit, $productUnitCode, $quantity, $productHolder);

        if ($this->shippingKitItemLineItemFactory !== null) {
            $builder->setShippingKitItemLineItemFactory($this->shippingKitItemLineItemFactory);
        }

        return $builder;
    }
}
