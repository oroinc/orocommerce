<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PaymentBundle\EventListener\PaymentShippingSurchargeListener;

class PaymentShippingSurchargeListenerTest extends AbstractSurchargeListenerTest
{
    /**
     * {@inheritDoc}
     */
    protected function getAmount(CollectSurchargeEvent $event): float|int
    {
        return $event->getSurchargeModel()->getShippingAmount();
    }

    /**
     * {@inheritDoc}
     */
    protected function getListener(): AbstractSurchargeListener
    {
        return new PaymentShippingSurchargeListener($this->provider);
    }
}
