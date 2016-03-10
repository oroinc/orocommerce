<?php

namespace OroB2B\Bundle\OrderBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;

use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider;

class MatchingPriceEventListener
{
    /** @var MatchingPriceProvider */
    protected $provider;

    /** @var PriceListTreeHandler */
    protected $priceListTreeHandler;

    /**
     * @param MatchingPriceProvider $provider
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(MatchingPriceProvider $provider, PriceListTreeHandler $priceListTreeHandler)
    {
        $this->provider = $provider;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $lineItems = $order->getLineItems()->map(
            function (OrderLineItem $orderLineItem) use ($order) {
                $product = $orderLineItem->getProduct();

                return [
                    'product' => $product ? $product->getId() : null,
                    'unit' => $orderLineItem->getProductUnit() ? $orderLineItem->getProductUnit()->getCode() : null,
                    'qty' => $orderLineItem->getQuantity(),
                    'currency' => $orderLineItem->getCurrency() ?: $order->getCurrency(),
                ];
            }
        );

        $priceList = $this->priceListTreeHandler->getPriceList($order->getAccount(), $order->getWebsite());
        $matchingPrices = $this->provider->getMatchingPrices($lineItems->toArray(), $priceList);

        $event->getData()->offsetSet('matchedPrices', $matchingPrices);
    }
}
