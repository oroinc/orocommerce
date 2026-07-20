<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;

/**
 * Provides tier prices for order line item.
 */
class OrderLineItemTierPricesProvider
{
    public function __construct(
        private readonly OrderProductPriceProvider $orderProductPriceProvider,
        private readonly ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider,
    ) {
    }

    /**
     * @param OrderLineItem $orderLineItem
     *
     * @return array<int|string, list<ProductPriceInterface>> Array where each key is a product ID and each value
     *  is a list of tier prices for that product. The array contains a single element if the order line item product
     *  is simple, or multiple elements if the order line item product is a kit.
     */
    public function getTierPricesForLineItem(OrderLineItem $orderLineItem): array
    {
        $order = $orderLineItem->getOrder();
        if ($order === null) {
            return [];
        }

        if (!$orderLineItem->getProduct()) {
            return [];
        }

        $currency = $order->getCurrency();
        if ($currency === null) {
            return [];
        }

        $productPricesByProduct = $this->orderProductPriceProvider
            ->getProductPricesForLineItems($order, [$orderLineItem]);
        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));

        $orderLineItemProductPrices = $this->productLineItemProductPriceProvider
            ->getProductLineItemProductPrices($orderLineItem, $productPriceCollection, $currency);
        if (!$orderLineItemProductPrices) {
            return [];
        }

        $productPricesByProduct[$orderLineItem->getProduct()->getId()] = $orderLineItemProductPrices;

        return $productPricesByProduct;
    }

    /**
     * Returns tier prices for multiple order line items using a single price-storage query.
     * The result array uses the same keys as the input $orderLineItems array.
     *
     * @param array<int|string, OrderLineItem> $orderLineItems
     *
     * @return array<int|string, list<ProductPriceInterface>> Array where each key matches the corresponding
     *  line item index from the input, and each value is a list of tier prices for that line item.
     */
    public function getTierPricesForLineItems(iterable $orderLineItems): array
    {
        if (!$orderLineItems) {
            return [];
        }

        $order = array_values($orderLineItems)[0]->getOrder();
        if ($order === null || $order->getCurrency() === null) {
            return array_fill_keys(array_keys($orderLineItems), []);
        }

        $productPricesByProduct = $this->orderProductPriceProvider
            ->getProductPricesForLineItems($order, $orderLineItems);
        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));

        $currency = $order->getCurrency();

        $lineItemsTierPrices = [];
        foreach ($orderLineItems as $key => $lineItem) {
            $product = $lineItem->getProduct();
            if ($product === null) {
                $lineItemsTierPrices[$key] = [];
                continue;
            }

            $lineItemsTierPrices[$key] = $this->productLineItemProductPriceProvider
                ->getProductLineItemProductPrices($lineItem, $productPriceCollection, $currency);
        }

        return $lineItemsTierPrices;
    }
}
