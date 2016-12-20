<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\BasicPaymentLineItemBuilder;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class BasicPaymentLineItemBuilderFactory implements PaymentLineItemBuilderFactoryInterface
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
        return new BasicPaymentLineItemBuilder($price, $productUnit, $productUnitCode, $quantity, $productHolder);
    }
}
