<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides tier prices for order line item.
 */
class OrderLineItemTierPricesProvider
{
    public function __construct(
        private readonly ProductPriceProviderInterface $productPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        private readonly ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider
    ) {
    }

    /**
     * @param OrderLineItem $orderLineItem
     *
     * @return array<int, array<ProductPriceCollectionDTO>>
     */
    public function getTierPricesForLineItem(OrderLineItem $orderLineItem): array
    {
        $order = $orderLineItem->getOrder();
        if ($order === null) {
            return [];
        }

        $products = $this->getProductsFromLineItem($orderLineItem);
        if (!$products) {
            return [];
        }

        $currency = $order->getCurrency();
        if ($currency === null) {
            return [];
        }

        $scopeCriteria = $this->priceScopeCriteriaFactory->createByContext($order);

        $productPricesByProduct = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $products,
            [$currency]
        );

        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));

        $productPricesByProduct[$orderLineItem->getProduct()->getId()] = $this->productLineItemProductPriceProvider
            ->getProductLineItemProductPrices($orderLineItem, $productPriceCollection, $currency);

        return $productPricesByProduct;
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return array<Product> Line item product including all related product kit item products.
     */
    private function getProductsFromLineItem(OrderLineItem $lineItem): array
    {
        $products = [];
        $product = $lineItem->getProduct();
        if ($product === null) {
            return [];
        }

        $products[$product->getId()] = $lineItem->getProduct();

        if ($product->isKit() !== true) {
            return array_values($products);
        }

        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
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

        return array_values($products);
    }
}
