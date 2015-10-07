<?php

namespace OroB2B\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;
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
     * @var AbstractPriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param AbstractPriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        AbstractPriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceListRequestHandler = $priceListRequestHandler;
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
            $tierPrices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
                $this->getPriceList($quote)->getId(),
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
        $productsPriceCriteria = $this->getProductsPriceCriteria($quote);

        if ($productsPriceCriteria) {
            $matchedPrices = $this->productPriceProvider->getMatchedPrices(
                $productsPriceCriteria,
                $this->getPriceList($quote)
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
            if ($quoteProduct->getProduct()) {
                $product = $quoteProduct->getProduct();
            } else {
                continue;
            }
            /** @var QuoteProductOffer $quoteProductOffer */
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                if ($quoteProductOffer->getProductUnit() && $quoteProductOffer->getQuantity()) {
                    $productsPriceCriteria[] = new ProductPriceCriteria(
                        $product,
                        $quoteProductOffer->getProductUnit(),
                        $quoteProductOffer->getQuantity(),
                        $quoteProductOffer->getPrice()->getCurrency()
                    );
                }
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param Quote $quote
     * @return PriceList
     */
    protected function getPriceList(Quote $quote)
    {
        $priceList = $quote->getPriceList();
        if (!$priceList) {
            $priceList = $this->priceListRequestHandler->getPriceList();
        }
        return $priceList;
    }
}
