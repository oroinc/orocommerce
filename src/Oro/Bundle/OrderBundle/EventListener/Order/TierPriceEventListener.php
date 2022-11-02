<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;

/**
 * Adds tier price info for products from order with given currency
 */
class TierPriceEventListener
{
    const TIER_PRICES_KEY = 'tierPrices';

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $products = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct();
            }
        );

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->priceScopeCriteriaFactory->createByContext($order),
            $products->toArray(),
            [$order->getCurrency()]
        );

        $event->getData()->offsetSet(self::TIER_PRICES_KEY, $prices);
    }
}
