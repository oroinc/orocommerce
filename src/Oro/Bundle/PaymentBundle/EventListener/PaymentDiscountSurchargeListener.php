<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

/**
 * Handles surcharge collection for discount amounts.
 *
 * This listener accumulates discount surcharges during the surcharge collection process,
 * allowing the payment system to account for discounts applied to orders.
 */
class PaymentDiscountSurchargeListener extends AbstractSurchargeListener
{
    #[\Override]
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setDiscountAmount($model->getDiscountAmount() + $amount);
    }
}
