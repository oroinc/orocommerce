<?php

namespace Oro\Bundle\SaleBundle\Quote\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;

/**
 * Decides if the price in a quote product item is not equal to a matching listed price.
 */
class QuotePricesComparator
{
    /**
     * @param BaseQuoteProductItem $quoteProductItem
     * @param array<ProductPriceInterface> $tierPrices
     *
     * @return bool
     */
    public function isPriceEqualsToMatchingListedPrice(
        BaseQuoteProductItem $quoteProductItem,
        array $tierPrices
    ): bool {
        if (!$tierPrices) {
            return false;
        }

        $price = $quoteProductItem->getPrice();
        if (!$price) {
            return false;
        }

        $matchingPrice = $this->findMatchingPrice($quoteProductItem, $tierPrices, $price->getCurrency());
        if ($matchingPrice === null) {
            return false;
        }

        return abs((float)$price->getValue() - (float)$matchingPrice->getValue()) <= 1e-6;
    }

    public function isPriceOneOfListedPrices(BaseQuoteProductItem $quoteProductItem, array $tierPrices): bool
    {
        if (!$tierPrices) {
            return false;
        }

        $price = $quoteProductItem->getPrice();
        if (!$price) {
            return false;
        }

        $listedPrices = $this->getListedProductPrices($quoteProductItem, $tierPrices, $price->getCurrency());
        foreach ($listedPrices as $productPrice) {
            if (abs((float)$price->getValue() - (float)$productPrice->getPrice()->getValue()) <= 1e-6) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param BaseQuoteProductItem $quoteProductItem
     * @param array $tierPrices
     * @param string $currency
     *
     * @return Price|null
     */
    private function findMatchingPrice(
        BaseQuoteProductItem $quoteProductItem,
        array $tierPrices,
        string $currency
    ): ?Price {
        $listedPrices = $this->getListedProductPrices($quoteProductItem, $tierPrices, $currency);
        $matchedQuantity = 0;
        $matchingPrice = null;

        foreach ($listedPrices as $productPrice) {
            if ($matchedQuantity <= $quoteProductItem->getQuantity()
                && $quoteProductItem->getQuantity() >= $productPrice->getQuantity()) {
                $matchedQuantity = $productPrice->getQuantity();
                $matchingPrice = $productPrice->getPrice();
            }
        }

        return $matchingPrice;
    }

    /**
     * @param BaseQuoteProductItem $quoteProductItem
     * @param array $tierPrices
     * @param string $currency
     *
     * @return iterable<ProductPriceInterface>
     */
    private function getListedProductPrices(
        BaseQuoteProductItem $quoteProductItem,
        array $tierPrices,
        string $currency
    ): iterable {
        return (new ProductPriceCollectionDTO($tierPrices))->getMatchingByCriteria(
            $quoteProductItem->getProduct()?->getId(),
            $quoteProductItem->getProductUnitCode(),
            $currency
        );
    }
}
