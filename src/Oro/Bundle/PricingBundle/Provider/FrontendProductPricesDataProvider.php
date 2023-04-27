<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Allows to get all or matched prices for products.
 */
class FrontendProductPricesDataProvider
{
    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    protected $scopeCriteriaRequestHandler;

    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

    /**
     * @param ProductLineItemInterface[] $lineItems
     * @return array
     */
    public function getProductsMatchedPrice(array $lineItems)
    {
        $prices = $this->productPriceProvider
            ->getMatchedPrices(
                $this->productPriceCriteriaFactory->createListFromProductLineItems($lineItems),
                $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
            );

        $result = [];
        foreach ($prices as $key => $price) {
            [$productId, $unitId] = explode('-', $key);
            $result[$productId][$unitId] = $price;
        }

        return $result;
    }

    /**
     * @param array<ProductHolderInterface> $lineItems
     *
     * @return array<int,array<string,array<ProductPriceInterface>>>
     */
    public function getAllPricesForLineItems(array $lineItems): array
    {
        return $this->getAllPricesForProducts($this->getProductsFromLineItems($lineItems));
    }

    /**
     * @param array<Product> $products
     *
     * @return array<int,array<string,array<ProductPriceInterface>>>
     */
    public function getAllPricesForProducts(array $products): array
    {
        if (!$products) {
            return [];
        }

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $products,
            [$this->userCurrencyManager->getUserCurrency()]
        );

        $pricesByUnit = [];
        /** @var ProductPriceInterface[] $productPrices */
        foreach ($prices as $productId => $productPrices) {
            $pricesByUnit[$productId] = [];
            foreach ($productPrices as $productPrice) {
                $pricesByUnit[$productId][$productPrice->getUnit()->getCode()][] = $productPrice;
            }
        }

        return $pricesByUnit;
    }

    /**
     * @param array|ProductHolderInterface[] $lineItems
     * @return array|Product[]
     */
    protected function getProductsFromLineItems(array $lineItems): array
    {
        return array_map(
            function (ProductHolderInterface $lineItem) {
                return $lineItem->getProduct();
            },
            array_filter(
                $lineItems,
                function (ProductHolderInterface $lineItem) {
                    return $this->isValidLineItem($lineItem);
                }
            )
        );
    }

    /**
     * @param ProductHolderInterface $lineItem
     * @return bool
     */
    protected function isValidLineItem(ProductHolderInterface $lineItem)
    {
        return $lineItem->getProduct() && $lineItem->getProduct()->getId();
    }
}
