<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentDiscountSurchargeListener;

class PaymentDiscountSurchargeListenerTest extends AbstractSurchargeListenerTest
{
    /**
     * {@inheritdoc}
     */
    protected function getAmount(CollectSurchargeEvent $event)
    {
        return $event->getSurchargeModel()->getDiscountAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener()
    {
        return new PaymentDiscountSurchargeListener($this->provider);
    }
}
