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
    private ProductPriceProviderInterface $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    private ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider;

    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->productLineItemProductPriceProvider = $productLineItemProductPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param Quote $quote
     * @param array<string> $currencies
     *
     * @return array<int,array<string,array<ProductPriceInterface>>> Array of arrays of {@see ProductPriceInterface}
     *  objects, keyed by a product id and quote product offer checksum.
     *  [
     *      64 => [ // product id
     *          'sample-checksum' => [ // quote product offer checksum
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
        $tierPrices = [];
        foreach ($this->doGetTierPrices($quote, $currencies) as [$productId, $checksum, $offerTierPrices]) {
            if (isset($tierPrices[$productId][$checksum])) {
                continue;
            }

            $tierPrices[$productId][$checksum] = $offerTierPrices;
        }

        return $tierPrices;
    }

    /**
     * @param Quote $quote
     * @param array<string> $currencies
     *
     * @return \Generator<ProductPriceInterface>
     */
    private function doGetTierPrices(Quote $quote, array $currencies = []): \Generator
    {
        if (!$currencies) {
            $currencies = $this->userCurrencyManager->getAvailableCurrencies();
        }

        $productPricesByProduct = $this->getProductPrices($quote, $currencies);
        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));

        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $product = $quoteProduct->getProduct();
            if ($product === null) {
                continue;
            }

            $productId = $product->getId();
            foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
                $quoteProductOffer->loadKitItemLineItems();

                $checksum = $quoteProductOffer->getChecksum();

                $quoteProductOfferTierPrices = [];
                foreach ($currencies as $currency) {
                    $productPrices = $this->productLineItemProductPriceProvider
                        ->getProductLineItemProductPrices($quoteProductOffer, $productPriceCollection, $currency);

                    if (!empty($productPrices)) {
                        $quoteProductOfferTierPrices[] = $productPrices;
                    }
                }

                yield [$productId, $checksum, array_merge(...$quoteProductOfferTierPrices)];
            }
        }
    }

    /**
     * @param Quote $quote
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

        return array_values($products);
    }

    /**
     * Checks whatever quote has line items with no prices set
     * @param Quote $quote
     * @return bool
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
