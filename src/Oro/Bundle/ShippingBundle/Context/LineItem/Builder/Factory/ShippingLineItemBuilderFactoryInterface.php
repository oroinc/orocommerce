<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;

interface ShippingLineItemBuilderFactoryInterface
{
    /**
     * @param ProductUnit            $productUnit
     * @param string                 $productUnitCode
     * @param int                    $quantity
     * @param ProductHolderInterface $productHolder
     *
     * @return ShippingLineItemBuilderInterface
     */
    public function createBuilder(
        ProductUnit $productUnit,
        $productUnitCode,
        $quantity,
        ProductHolderInterface $productHolder
    );
}
