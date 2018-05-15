<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\PaymentShippingSurchargeListener;

class PaymentShippingSurchargeListenerTest extends AbstractSurchargeListenerTest
{
    /**
     * {@inheritdoc}
     */
    protected function getAmount(CollectSurchargeEvent $event)
    {
        return $event->getSurchargeModel()->getShippingAmount();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener()
    {
        return new PaymentShippingSurchargeListener($this->provider);
    }
}
