<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem;

use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

interface ApruveLineItemBuilderFactoryInterface
{
    /**
     * @param PaymentLineItemInterface $paymentLineItem
     *
     * @return ApruveLineItemBuilderInterface
     */
    public function create(PaymentLineItemInterface $paymentLineItem);
}
