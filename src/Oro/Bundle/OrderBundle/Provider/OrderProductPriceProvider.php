<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides available product prices for the products (including related product kit item products) present
 * in the order line items of an order.
 */
class OrderProductPriceProvider
{
    private ProductPriceProviderInterface $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * @param Order $order
     *
     * @return array<int,array<ProductPriceInterface>> Array of arrays of {@see ProductPriceInterface} objects,
     *   keyed by an order line item product id, including related product kit item products.
     */
    public function getProductPrices(Order $order): array
    {
        $orderLineItems = $order->getLineItems();
        $products = $this->getProductsFromLineItems($orderLineItems);
        if (!$products) {
            return [];
        }

        /** @var array<int,array<ProductPriceInterface>> $productPricesByProduct */
        $productPricesByProduct = $this->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts(
                $this->priceScopeCriteriaFactory->createByContext($order),
                $products,
                [$order->getCurrency()]
            );

        return $productPricesByProduct;
    }

    /**
     * @param iterable<OrderLineItem> $lineItems
     *
     * @return array<Product> Line item products including all related product kit item products.
     */
    private function getProductsFromLineItems(iterable $lineItems): array
    {
        $products = [];
        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            if ($product === null) {
                continue;
            }

            $products[$product->getId()] = $lineItem->getProduct();

            if ($product->isKit() !== true) {
                continue;
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
        }

        return array_values($products);
    }
}
