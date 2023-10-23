<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

/**
 * Interface for the factory creating a collection of shipping line item models.
 *
 * @deprecated since 5.1
 */
interface PaymentLineItemCollectionFactoryInterface
{
    /**
     * @param array|PaymentLineItemInterface[] $paymentLineItems
     *
     * @return PaymentLineItemCollectionInterface
     */
    public function createPaymentLineItemCollection(array $paymentLineItems);
}
