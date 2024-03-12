<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;

/**
 * Provides product prices for all related to the specified request entity products.
 */
class RequestProductPriceProvider
{
    private ProductPriceProviderInterface $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param Request $request
     * @param string|null $currency
     *
     * @return array<int,array<ProductPriceInterface>> Array of arrays of {@see ProductPriceInterface} objects,
     *   keyed by a product id, including related product kit item products.
     */
    public function getProductPrices(Request $request, ?string $currency = null): array
    {
        $requestProducts = $request->getRequestProducts();
        $products = $this->getProducts($requestProducts);
        if (!$products) {
            return [];
        }

        if ($currency === null) {
            $currency = $this->userCurrencyManager->getUserCurrency($request->getWebsite());
        }

        /** @var array<int,array<ProductPriceInterface>> $productPricesByProduct */
        $productPricesByProduct = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts(
                $this->priceScopeCriteriaFactory->createByContext($request),
                $products,
                [$currency]
            );

        return $productPricesByProduct;
    }

    /**
     * @param iterable<RequestProduct> $requestProducts
     *
     * @return array<Product> Line item products including all related product kit item products.
     */
    private function getProducts(iterable $requestProducts): array
    {
        $products = [];
        foreach ($requestProducts as $requestProduct) {
            $product = $requestProduct->getProduct();
            if ($product === null) {
                continue;
            }

            $products[$product->getId()] = $requestProduct->getProduct();

            if ($product->isKit() !== true) {
                continue;
            }

            foreach ($requestProduct->getKitItemLineItems() as $kitItemLineItem) {
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
}
