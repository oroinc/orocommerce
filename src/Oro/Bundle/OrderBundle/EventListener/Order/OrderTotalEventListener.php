<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;

class OrderTotalEventListener
{
    const TOTALS_KEY = 'totals';

    /** @var TotalProvider */
    protected $provider;

    public function __construct(TotalProvider $provider)
    {
        $this->provider = $provider;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $totals = $this->provider->getTotalWithSubtotalsWithBaseCurrencyValues($order, false);

        $event->getData()->offsetSet(self::TOTALS_KEY, $totals);
    }
}
