<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;

class SurchargeProvider
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
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
        $this->eventDispatcher->dispatch(CollectSurchargeEvent::NAME, $event);

        return $event->getSurchargeModel();
    }
}
