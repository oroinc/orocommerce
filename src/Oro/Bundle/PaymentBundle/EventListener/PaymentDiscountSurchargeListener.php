<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

class PaymentDiscountSurchargeListener extends AbstractSurchargeListener
{
    #[\Override]
    protected function setAmount(Surcharge $model, $amount)
    {
        $model->setDiscountAmount($model->getDiscountAmount() + $amount);
    }
}
