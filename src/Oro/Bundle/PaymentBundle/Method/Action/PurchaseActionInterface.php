<?php

namespace Oro\Bundle\PaymentBundle\Method\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Payment Method with Purchase support
 */
interface PurchaseActionInterface
{
    public function purchase(PaymentTransaction $paymentTransaction): array;
}
