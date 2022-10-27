<?php

namespace Oro\Bundle\SaleBundle\Quote\Pricing;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;

/**
 * Decides if product prices in the quote were changed
 */
class QuotePriceComparator
{
    /** @var Quote */
    protected $quote;

    /** @var QuoteProductPriceProvider */
    protected $provider;

    public function __construct(
        Quote $quote,
        QuoteProductPriceProvider $provider
    ) {
        $this->quote = $quote;
        $this->provider = $provider;
    }

    /**
     * @param string $productSku
     * @param string $productUnit
     * @param float|int $quantity
     * @param string $currency
     * @param float $price
     * @return bool
     */
    public function isQuoteProductOfferPriceChanged($productSku, $productUnit, $quantity, $currency, $price)
    {
        $matchedPrice = $this->provider->getMatchedProductPrice(
            $this->quote,
            $productSku,
            $productUnit,
            $quantity,
            $currency
        );

        if ($matchedPrice === null) {
            return true;
        }

        return abs((float)$price - (float)$matchedPrice->getValue()) > 1e-6;
    }
}
