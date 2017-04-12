<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveLineItemBuilderInterface;
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
