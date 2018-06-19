<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductPriceProvider
{
    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     */
    public function __construct(ProductPriceProviderInterface $productPriceProvider)
    {
        $this->productPriceProvider = $productPriceProvider;
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
            $tierPrices = $this->productPriceProvider->getPricesAsArrayByScopeCriteriaAndProductIds(
                $this->getPriceScopeCriteria($quote),
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
        $productsPriceCriteria = $this->getProductsPriceCriteria($quote);

        if ($productsPriceCriteria) {
            $scopeCriteria = $this->getPriceScopeCriteria($quote);
            $matchedPrices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $scopeCriteria);
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
     * @return ProductPriceScopeCriteriaInterface
     */
    protected function getPriceScopeCriteria(Quote $quote): ProductPriceScopeCriteriaInterface
    {
        $searchScope = new ProductPriceScopeCriteria();
        $searchScope->setCustomer($quote->getCustomer());
        $searchScope->setWebsite($quote->getWebsite());

        return $searchScope;
    }
}
