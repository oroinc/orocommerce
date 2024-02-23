<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;

/**
 * Adds "tierPrices" to the quote entry point data.
 */
class QuoteProductTierPricesQuoteEventListener
{
    public const TIER_PRICES_KEY = 'tierPrices';

    private QuoteProductPricesProvider $quoteProductPricesProvider;

    public function __construct(QuoteProductPricesProvider $quoteProductPricesProvider)
    {
        $this->quoteProductPricesProvider = $quoteProductPricesProvider;
    }

    public function onQuoteEvent(QuoteEvent $event): void
    {
        $quote = $event->getQuote();

        $event->getData()->offsetSet(
            self::TIER_PRICES_KEY,
            $this->quoteProductPricesProvider->getProductLineItemsTierPrices($quote)
        );
    }
}
