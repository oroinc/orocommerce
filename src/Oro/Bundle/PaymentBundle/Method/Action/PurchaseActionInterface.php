<?php

namespace Oro\Bundle\PaymentBundle\Method\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * Payment Method with Purchase support
 */
interface PurchaseActionInterface
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function purchase(PaymentTransaction $paymentTransaction): array;
}
