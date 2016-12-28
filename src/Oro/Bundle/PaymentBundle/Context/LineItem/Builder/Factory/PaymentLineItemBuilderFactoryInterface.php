<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\PaymentLineItemBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

interface PaymentLineItemBuilderFactoryInterface
{
    /**
     * @param Price $price
     * @param ProductUnit $productUnit
     * @param string $productUnitCode
     * @param int $quantity
     * @param ProductHolderInterface $productHolder
     *
     * @return PaymentLineItemBuilderInterface
     */
    public function createBuilder(
        Price $price,
        ProductUnit $productUnit,
        $productUnitCode,
        $quantity,
        ProductHolderInterface $productHolder
    );
}
