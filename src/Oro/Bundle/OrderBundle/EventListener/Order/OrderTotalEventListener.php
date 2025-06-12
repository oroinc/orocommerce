<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Provider\TotalProvider;

/**
 * The class set order totals to form
 */
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

        $totals = $this->provider->getTotalFromOrderWithSubtotalsWithBaseCurrencyValues($order, false);

        $event->getData()->offsetSet(self::TOTALS_KEY, $totals);
    }
}
