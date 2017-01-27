<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BasicMethodTypeRemovalEventDispatcher implements MethodTypeRemovalEventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($methodId, $typeId)
    {
        $this->eventDispatcher->dispatch(MethodTypeRemovalEvent::NAME, new MethodTypeRemovalEvent($methodId, $typeId));
    }
}
