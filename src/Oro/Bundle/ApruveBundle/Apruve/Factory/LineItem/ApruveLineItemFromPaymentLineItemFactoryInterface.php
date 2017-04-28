<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

interface ApruveLineItemFromPaymentLineItemFactoryInterface
{
    /**
     * @param PaymentLineItemInterface $paymentLineItem
     *
     * @return ApruveLineItem
     */
    public function createFromPaymentLineItem(PaymentLineItemInterface $paymentLineItem);
}
