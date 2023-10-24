<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Provider\OrderProductPriceProvider;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;

/**
 * Adds "tierPrices" to the order entry point data.
 */
class OrderLineItemTierPricesEventListener
{
    public const TIER_PRICES_KEY = 'tierPrices';

    private OrderProductPriceProvider $orderProductPriceProvider;

    private ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider;

    public function __construct(
        OrderProductPriceProvider $orderProductPriceProvider,
        ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider
    ) {
        $this->orderProductPriceProvider = $orderProductPriceProvider;
        $this->productLineItemProductPriceProvider = $productLineItemProductPriceProvider;
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        $order = $event->getOrder();
        $currency = $order->getCurrency();
        $productPricesByProduct = $this->orderProductPriceProvider->getProductPrices($order);
        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));
        $productPricesByProductAndChecksum = [];

        foreach ($order->getLineItems() as $orderLineItem) {
            $product = $orderLineItem->getProduct();
            if ($product === null) {
                continue;
            }

            $productPrices = $this->productLineItemProductPriceProvider
                ->getProductLineItemProductPrices($orderLineItem, $productPriceCollection, $currency);

            if (!empty($productPrices)) {
                $productId = $product->getId();
                if ($product->isKit()) {
                    $productPricesByProductAndChecksum[$productId][$orderLineItem->getChecksum()] = $productPrices;
                } else {
                    $productPricesByProduct[$productId] = $productPrices;
                }
            }
        }

        $event->getData()->offsetSet(
            self::TIER_PRICES_KEY,
            array_replace($productPricesByProduct, $productPricesByProductAndChecksum)
        );
    }
}
