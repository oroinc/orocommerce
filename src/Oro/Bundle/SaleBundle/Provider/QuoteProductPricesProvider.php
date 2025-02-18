<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;

/**
 * Handles logic for getting prices for certain quote
 */
class QuoteProductPricesProvider
{
    public function __construct(
        private ProductPriceProviderInterface $productPriceProvider,
        private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        private ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider,
        private UserCurrencyManager $userCurrencyManager
    ) {
    }

    /**
     * @param array<string> $currencies
     *
     * @return array<int,array<string,array<ProductPriceInterface>>> Array of arrays of {@see ProductPriceInterface}
     *  objects, keyed by a product id and quote product offer checksum.
     *  [
     *      64 => [ // product id
     *          'checksum-for-kit-product' => [ // quote product offer checksum
     *              ProductPriceInterface $productPrice,
     *              // ...
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function getProductLineItemsTierPrices(Quote $quote, array $currencies = []): array
    {
        if (!$currencies) {
            $currencies = $this->userCurrencyManager->getAvailableCurrencies();
        }

        $productPrices = $this->getProductPrices($quote, $currencies);
        $quoteProductOffersPrices = $this->getQuoteProductOffersPrices($quote, $productPrices, $currencies);

        foreach ($quoteProductOffersPrices as $productId => $checksumPrices) {
            foreach ($checksumPrices as $checksum => $prices) {
                $productPrices[$productId][$checksum] = $prices;
            }
        }

        return $productPrices;
    }

    /**
     * @param array<int,array<ProductPriceInterface>> $productPrices
     * @param array<string> $currencies
     *
     * @return array<int,array<string,array<ProductPriceInterface>>> Array of arrays of {@see ProductPriceInterface}
     *  objects, keyed by a product id and quote product offer.
     *  [
     *      64 => ['checksum-for-kit-product' => [ProductPriceInterface $productPrice, ...], ...], //
     *      1 => [0 => [ProductPriceInterface $productPrice], 1 => [ProductPriceInterface $productPrice], ...]
     *  ]
     */
    private function getQuoteProductOffersPrices(Quote $quote, array $productPrices, array $currencies = []): array
    {
        $prices = [];
        $productPriceCollection = new ProductPriceCollectionDTO(\array_merge(...$productPrices));

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            if ($product === null) {
                continue;
            }

            $productId = $product->getId();
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $quoteProductOffer->loadKitItemLineItems();

                $quoteProductOfferTierPrices = [];
                foreach ($currencies as $currency) {
                    $quoteProductPrices = $this->productLineItemProductPriceProvider
                        ->getProductLineItemProductPrices($quoteProductOffer, $productPriceCollection, $currency);

                    if (!empty($quoteProductPrices)) {
                        $quoteProductOfferTierPrices[] = $quoteProductPrices;
                    }
                }

                $checksum = $quoteProductOffer->getChecksum();
                if (!isset($prices[$productId][$checksum])) {
                    $prices[$productId][$checksum] = \array_merge(...$quoteProductOfferTierPrices);
                }
            }
        }

        return $prices;
    }

    /**
     * @param array<string> $currencies
     *
     * @return array<int,array<ProductPriceInterface>> Array of arrays of {@see ProductPriceInterface} objects,
     *   keyed by a product id, including related product kit item products.
     */
    public function getProductPrices(Quote $quote, array $currencies = []): array
    {
        $quoteProducts = $quote->getQuoteProducts();
        $products = $this->getProducts($quoteProducts);
        if (!$products) {
            return [];
        }

        $priceScopeCriteria = $this->priceScopeCriteriaFactory->createByContext($quote);
        if (!$currencies) {
            $currencies = $this->productPriceProvider->getSupportedCurrencies($priceScopeCriteria);
        }

        /** @var array<int,array<ProductPriceInterface>> $productPricesByProduct */
        $productPricesByProduct = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts($priceScopeCriteria, $products, $currencies);

        return $productPricesByProduct;
    }

    /**
     * @param iterable<QuoteProduct> $quoteProducts
     *
     * @return array<Product> Line item products (including all related product kit item products).
     */
    private function getProducts(iterable $quoteProducts): array
    {
        $products = [];
        foreach ($quoteProducts as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            if ($product === null) {
                continue;
            }

            $products[$product->getId()] = $quoteProduct->getProduct();

            if ($product->isKit() !== true) {
                continue;
            }

            foreach ($quoteProduct->getKitItemLineItems() as $kitItemLineItem) {
                $kitItemProduct = $kitItemLineItem->getProduct();
                if ($kitItemProduct === null) {
                    continue;
                }

                $products[$kitItemProduct->getId()] = $kitItemProduct;
            }

            foreach ($product->getKitItems() as $kitItem) {
                foreach ($kitItem->getProducts() as $kitItemProduct) {
                    $products[$kitItemProduct->getId()] = $kitItemProduct;
                }
            }
        }

        return \array_values($products);
    }

    /**
     * Checks whatever quote has line items with no prices set
     */
    public function hasEmptyPrice(Quote $quote): bool
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
