<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;

class MatchingPriceEventListener
{
    const MATCHED_PRICES_KEY = 'matchedPrices';

    /** @var PriceMatcher */
    protected $priceMatcher;

    /**
     * @param PriceMatcher $priceMatcher
     */
    public function __construct(PriceMatcher $priceMatcher)
    {
        $this->priceMatcher = $priceMatcher;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $matchingPrices = $this->priceMatcher->getMatchingPrices($order);

        $event->getData()->offsetSet(self::MATCHED_PRICES_KEY, $matchingPrices);
    }
}
