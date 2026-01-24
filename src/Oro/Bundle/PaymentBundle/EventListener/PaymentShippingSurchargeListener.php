<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

/**
 * Handles surcharge collection for shipping amounts.
 *
 * This listener accumulates shipping surcharges during the surcharge collection process,
 * allowing the payment system to account for shipping costs in payment calculations.
 */
class PaymentShippingSurchargeListener extends AbstractSurchargeListener
{
    #[\Override]
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setShippingAmount($model->getShippingAmount() + $amount);
    }
}
