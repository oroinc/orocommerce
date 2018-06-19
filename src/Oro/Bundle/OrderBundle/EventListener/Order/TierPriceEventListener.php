<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;

class TierPriceEventListener
{
    const TIER_PRICES_KEY = 'tierPrices';

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     */
    public function __construct(ProductPriceProviderInterface $productPriceProvider)
    {
        $this->productPriceProvider = $productPriceProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $productIds = $order->getLineItems()->map(
            function (OrderLineItem $orderLineItem) {
                $product = $orderLineItem->getProduct();

                if (!$product) {
                    return false;
                }

                return $product->getId();
            }
        );

        $searchScope = new ProductPriceScopeCriteria();
        $searchScope->setCustomer($order->getCustomer());
        $searchScope->setWebsite($order->getWebsite());
        $prices = $this->productPriceProvider->getPricesAsArrayByScopeCriteriaAndProductIds(
            $searchScope,
            array_filter($productIds->toArray()),
            $order->getCurrency()
        );

        $event->getData()->offsetSet(self::TIER_PRICES_KEY, $prices);
    }
}
