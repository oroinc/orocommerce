<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SurchargeProvider
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param object $entity
     * @return Surcharge
     */
    public function getSurcharges($entity)
    {
        $event = new CollectSurchargeEvent($entity);
        $this->eventDispatcher->dispatch($event, CollectSurchargeEvent::NAME);

        return $event->getSurchargeModel();
    }
}
