<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Handles logic for getting prices for certain quote
 */
class QuoteProductPriceProvider
{
    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var PriceListTreeHandler
     */
    protected $treeHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param PriceListTreeHandler $treeHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        PriceListTreeHandler $treeHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->treeHandler = $treeHandler;
    }

    /**
     * @param Quote $quote
     *
     * @return array
     */
    public function getTierPrices(Quote $quote)
    {
        $productIds = $quote->getQuoteProducts()->filter(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct() !== null;
            }
        )->map(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct()->getId();
            }
        );

        return $this->fetchTierPrices($quote, $productIds->toArray());
    }

    /**
     * @param Quote $quote
     * @param array $productIds
     *
     * @return array
     */
    public function getTierPricesForProducts(Quote $quote, array $productIds)
    {
        return $this->fetchTierPrices($quote, $productIds);
    }

    /**
     * @param Quote $quote
     * @param array $productIds
     *
     * @return array
     */
    protected function fetchTierPrices(Quote $quote, array $productIds)
    {
        $tierPrices = [];

        if ($productIds) {
            $priceList = $this->getPriceList($quote);
            if (!$priceList) {
                return [];
            }
            $tierPrices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
                $priceList->getId(),
                $productIds
            );
            if (!$tierPrices) {
                $tierPrices = [];
            }
        }

        return $tierPrices;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function getMatchedPrices(Quote $quote)
    {
        $matchedPrices = [];
        $priceList = $this->getPriceList($quote);
        if (!$priceList) {
            return [];
        }
        $productsPriceCriteria = $this->getProductsPriceCriteria($quote);

        if ($productsPriceCriteria) {
            $matchedPrices = $this->productPriceProvider->getMatchedPrices(
                $productsPriceCriteria,
                $priceList
            );
        }

        /** @var Price $price */
        foreach ($matchedPrices as &$price) {
            if ($price) {
                $price = [
                    'value' => $price->getValue(),
                    'currency' => $price->getCurrency()
                ];
            }
        }

        return $matchedPrices;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    protected function getProductsPriceCriteria(Quote $quote)
    {
        $productsPriceCriteria = [];

        /** @var QuoteProduct $quoteProduct */
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            if (!$quoteProduct->getProduct()) {
                continue;
            }

            $product = $quoteProduct->getProduct();

            /** @var QuoteProductOffer $quoteProductOffer */
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if (!$quoteProductOffer->getProductUnit() ||
                    !$quoteProductOffer->getQuantity() ||
                    !$quoteProductOffer->getPrice()
                ) {
                    continue;
                }

                $productsPriceCriteria[] = new ProductPriceCriteria(
                    $product,
                    $quoteProductOffer->getProductUnit(),
                    $quoteProductOffer->getQuantity(),
                    $quoteProductOffer->getPrice()->getCurrency()
                );
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param Quote $quote
     * @return BasePriceList
     */
    protected function getPriceList(Quote $quote)
    {
        return $this->treeHandler->getPriceList($quote->getCustomer(), $quote->getWebsite());
    }

    /**
     * Checks whatever quote has line items with no prices set
     * @param Quote $quote
     * @return bool
     */
    public function hasEmptyPrice(Quote $quote)
    {
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            if (!$product) {
                continue;
            }

            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if ($quoteProductOffer->getPrice() === null || $quoteProductOffer->getPrice()->getValue() === null) {
                    return true;
                }
            }
        }

        return false;
    }
}
