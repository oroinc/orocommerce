<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

class PaymentShippingSurchargeListener extends AbstractSurchargeListener
{
    #[\Override]
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setShippingAmount($model->getShippingAmount() + $amount);
    }
}
