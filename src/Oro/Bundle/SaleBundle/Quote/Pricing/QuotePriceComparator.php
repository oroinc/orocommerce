<?php

namespace Oro\Bundle\SaleBundle\Quote\Pricing;

use Oro\Bundle\SaleBundle\Entity\Quote;

class QuotePriceComparator
{
    /** @var Quote */
    protected $quote;

    /** @var array */
    protected $quotePrices;

    /**
     * @param Quote $quote
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
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
        $this->ensureQuotePrices();

        $key = $this->getKey($productSku, $productUnit, $quantity, $currency);

        return !isset($this->quotePrices[$key]) || $this->quotePrices[$key] !== (float)$price;
    }

    private function ensureQuotePrices()
    {
        if ($this->quotePrices !== null) {
            return;
        }

        $this->quotePrices = [];
        foreach ($this->quote->getQuoteProducts() as $quoteProduct) {
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $price = $quoteProductOffer->getPrice();
                if (!$price) {
                    continue;
                }

                $key = $this->getKey(
                    $quoteProduct->getProductSku(),
                    $quoteProductOffer->getProductUnitCode(),
                    $quoteProductOffer->getQuantity(),
                    $price->getCurrency()
                );

                $this->quotePrices[$key] = (float)$price->getValue();
            }
        }
    }

    /**
     * @param string $productSku
     * @param string $productUnit
     * @param int $quantity
     * @param string $currency
     *
     * @return string
     */
    private function getKey($productSku, $productUnit, $quantity, $currency)
    {
        return sprintf('%s_%s_%s_%s', $productSku, $productUnit, $quantity, $currency);
    }
}
