<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalsProvider;

class OrderSubtotalsEventListener
{
    /** @var SubtotalsProvider */
    protected $provider;

    /**
     * @param SubtotalsProvider $provider
     */
    public function __construct(SubtotalsProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $subtotals = $this->provider->getSubtotals($order);
        $subtotals = $subtotals
            ->map(
                function (Subtotal $subtotal) {
                    return $subtotal->toArray();
                }
            )
            ->toArray();

        $event->getData()->offsetSet('subtotals', $subtotals);
    }
}
