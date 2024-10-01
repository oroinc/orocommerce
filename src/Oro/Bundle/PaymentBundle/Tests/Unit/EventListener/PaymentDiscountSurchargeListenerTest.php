<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\EventListener\AbstractSurchargeListener;
use Oro\Bundle\PaymentBundle\EventListener\PaymentDiscountSurchargeListener;

class PaymentDiscountSurchargeListenerTest extends AbstractSurchargeListenerTest
{
    #[\Override]
    protected function getAmount(CollectSurchargeEvent $event): float|int
    {
        return $event->getSurchargeModel()->getDiscountAmount();
    }

    #[\Override]
    protected function getListener(): AbstractSurchargeListener
    {
        return new PaymentDiscountSurchargeListener($this->provider);
    }
}
