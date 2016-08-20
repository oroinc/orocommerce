<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderTotalEventListener
{
    const TOTALS_KEY = 'totals';

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

        $totals = $this->provider->getTotalWithSubtotalsAsArray($order);

        $event->getData()->offsetSet(self::TOTALS_KEY, $totals);
    }
}
