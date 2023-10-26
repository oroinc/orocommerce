<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;

/**
 * Adds "matchedPrices" to the order entry point data.
 *
 * @deprecated since 5.1
 */
class MatchingPriceEventListener
{
    const MATCHED_PRICES_KEY = 'matchedPrices';

    /** @var PriceMatcher */
    protected $priceMatcher;

    public function __construct(PriceMatcher $priceMatcher)
    {
        $this->priceMatcher = $priceMatcher;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $matchingPrices = $this->priceMatcher->getMatchingPrices($order);

        $event->getData()->offsetSet(self::MATCHED_PRICES_KEY, $matchingPrices);
    }
}
