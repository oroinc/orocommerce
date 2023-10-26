<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\BasicPaymentLineItemBuilder;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Factory\PaymentLineItemBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

/**
 * Basic implementation of the payment line item model builder factory.
 *
 * @deprecated since 5.1
 */
class BasicPaymentLineItemBuilderFactory implements PaymentLineItemBuilderFactoryInterface
{
    private ?PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface $paymentKitItemLineItemFactory = null;

    public function setPaymentKitItemLineItemFactory(
        ?PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface $paymentKitItemLineItemFactory
    ): void {
        $this->paymentKitItemLineItemFactory = $paymentKitItemLineItemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilder(
        ProductUnit $productUnit,
        $productUnitCode,
        $quantity,
        ProductHolderInterface $productHolder
    ) {
        $builder = new BasicPaymentLineItemBuilder($productUnit, $productUnitCode, $quantity, $productHolder);

        if ($this->paymentKitItemLineItemFactory !== null) {
            $builder->setPaymentKitItemLineItemFactory($this->paymentKitItemLineItemFactory);
        }

        return $builder;
    }
}
