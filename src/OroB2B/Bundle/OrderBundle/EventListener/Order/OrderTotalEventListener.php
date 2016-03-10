<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderTotalEventListener
{
    /** @var TotalProcessorProvider */
    protected $provider;

    /**
     * @param TotalProcessorProvider $provider
     */
    public function __construct(TotalProcessorProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $total = $this->provider->getTotal($order)->toArray();

        $event->getData()->offsetSet('total', $total);
    }
}
