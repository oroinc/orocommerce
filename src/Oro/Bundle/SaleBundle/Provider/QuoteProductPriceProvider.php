<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Handles logic for getting prices for certain quote
 */
class QuoteProductPriceProvider
{
    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @var ProductPriceScopeCriteriaFactoryInterface
     */
    protected $priceScopeCriteriaFactory;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        DoctrineHelper $doctrineHelper
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->doctrineHelper = $doctrineHelper;
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
                return $quoteProduct->getProduct();
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
        $products = array_map(
            function ($productId) {
                return $this->doctrineHelper->getEntityReference(Product::class, $productId);
            },
            array_filter($productIds)
        );

        return $this->fetchTierPrices($quote, $products);
    }

    /**
     * @param Quote $quote
     * @param array|Product[] $products
     *
     * @return array
     */
    protected function fetchTierPrices(Quote $quote, array $products)
    {
        $tierPrices = [];

        if (count($products) > 0) {
            $tierPrices = $this->productPriceProvider->getPricesByScopeCriteriaAndProductIds(
                $this->priceScopeCriteriaFactory->createByContext($quote),
                $products
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
            $scopeCriteria = $this->priceScopeCriteriaFactory->createByContext($quote);
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
