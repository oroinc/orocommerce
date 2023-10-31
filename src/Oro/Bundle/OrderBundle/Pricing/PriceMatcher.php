<?php

namespace Oro\Bundle\OrderBundle\Pricing;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;

/**
 * Match prices by order line items.
 */
class PriceMatcher
{
    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    public function addMatchingPrices(Order $order): void
    {
        $lineItems = [];
        foreach ($order->getLineItems() as $key => $lineItem) {
            if ($lineItem->getProduct() === null) {
                continue;
            }

            if ($lineItem->getCurrency() === null || $lineItem->getValue() === null) {
                $lineItems[$key] = $lineItem;
            }
        }

        if ($lineItems) {
            $productLineItemsPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                $lineItems,
                $this->priceScopeCriteriaFactory->createByContext($order),
                $order->getCurrency()
            );

            foreach ($lineItems as $key => $lineItem) {
                if (!isset($productLineItemsPrices[$key])) {
                    continue;
                }

                $this->fillPrice($lineItem, $productLineItemsPrices[$key]);
            }
        }
    }

    private function fillPrice(OrderLineItem $lineItem, ProductLineItemPrice $productLineItemPrice): void
    {
        $lineItem->setPrice(clone($productLineItemPrice->getPrice()));
        if ($lineItem->getProduct()->isKit() !== true) {
            return;
        }

        if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
            return;
        }

        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemPrice = $productLineItemPrice->getKitItemLineItemPrice($kitItemLineItem);
            if ($kitItemLineItemPrice === null) {
                continue;
            }

            $kitItemLineItem->setPrice(clone($kitItemLineItemPrice->getPrice()));
        }
    }
}
