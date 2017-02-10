<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

class PaymentDiscountSurchargeListener extends AbstractSurchargeListener
{
    /**
     * {@inheritdoc}
     */
    protected function setAmount(Surcharge $model, $amount)
    {
        // TODO: This listener should work with discounts for checkout in BB-4834
        $model->setDiscountAmount($model->getDiscountAmount() + $amount);
    }
}
