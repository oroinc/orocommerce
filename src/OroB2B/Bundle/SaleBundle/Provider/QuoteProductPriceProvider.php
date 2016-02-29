<?php

namespace OroB2B\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

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
     * @return array
     */
    public function getTierPrices(Quote $quote)
    {
        $tierPrices = [];

        $productIds = $quote->getQuoteProducts()->filter(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct() !== null;
            }
        )->map(
            function (QuoteProduct $quoteProduct) {
                return $quoteProduct->getProduct()->getId();
            }
        );

        if ($productIds) {
            $priceList = $this->getPriceList($quote);
            if (!$priceList) {
                return [];
            }
            $tierPrices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
                $priceList->getId(),
                $productIds->toArray()
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
        return $this->treeHandler->getPriceList($quote->getAccount(), $quote->getWebsite());
    }
}
