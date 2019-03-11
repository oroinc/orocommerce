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

    /** @var array */
    protected $quotePrices;

    /** @var QuoteProductPriceProvider */
    protected $provider;

    /**
     * @param Quote $quote
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @param QuoteProductPriceProvider $provider
     */
    public function setQuoteProductPriceProvider(QuoteProductPriceProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return QuoteProductPriceProvider
     * @throws \InvalidArgumentException
     */
    private function getProvider()
    {
        if (!is_a($this->provider, QuoteProductPriceProvider::class)) {
            throw new \InvalidArgumentException(sprintf(
                'quoteProductPriceProvider should be instance of %s',
                QuoteProductPriceProvider::class
            ));
        }

        return $this->provider;
    }

    /**
     * @param string $productSku
     * @param string $productUnit
     * @param int $quantity
     * @param string $currency
     * @param float $price
     * @return bool
     */
    public function isQuoteProductOfferPriceChanged($productSku, $productUnit, $quantity, $currency, $price)
    {
        $matchedPrice = $this->getProvider()->getMatchedProductPrice(
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
