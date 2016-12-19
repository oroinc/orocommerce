<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

class DoctrinePaymentLineItemCollectionFactory implements PaymentLineItemCollectionFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPaymentLineItemCollection(array $paymentLineItems)
    {
        foreach ($paymentLineItems as $paymentLineItem) {
            if (!$paymentLineItem instanceof PaymentLineItemInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Expected: %s', PaymentLineItemInterface::class)
                );
            }
        }

        return new DoctrinePaymentLineItemCollection($paymentLineItems);
    }
}
