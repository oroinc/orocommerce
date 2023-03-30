<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
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

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
    }

    /**
     * @param ProductLineItemInterface[] $lineItems
     * @return array
     */
    public function getProductsMatchedPrice(array $lineItems)
    {
        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);
        $prices = $this->productPriceProvider
            ->getMatchedPrices(
                $productsPriceCriteria,
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
     * @param array|ProductLineItemInterface[] $lineItems
     * @return array
     */
    protected function getProductsPricesCriteria(array $lineItems)
    {
        $productsPricesCriteria = [];
        $currency = $this->userCurrencyManager->getUserCurrency();
        foreach ($lineItems as $lineItem) {
            if (!$this->isValidLineItem($lineItem)) {
                continue;
            }

            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $currency
            );
        }

        return $productsPricesCriteria;
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
