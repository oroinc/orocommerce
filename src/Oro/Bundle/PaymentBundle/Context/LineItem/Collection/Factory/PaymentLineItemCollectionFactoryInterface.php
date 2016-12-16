<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

interface PaymentLineItemCollectionFactoryInterface
{
    /**
     * @param array|PaymentLineItemInterface[] $paymentLineItems
     *
     * @return PaymentLineItemCollectionInterface
     */
    public function createPaymentLineItemCollection(array $paymentLineItems);
}
