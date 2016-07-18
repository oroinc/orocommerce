<?php

namespace OroB2B\Bundle\OrderBundle\Pricing;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider;

class PriceMatcher
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
     * @param Order $order
     * @return array
     */
    public function getMatchingPrices(Order $order)
    {
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

        return $this->provider->getMatchingPrices($lineItems->toArray(), $priceList);
    }

    /**
     * @param Order $order
     * @param array $matchedPrices
     */
    public function fillMatchingPrices(Order $order, array $matchedPrices = [])
    {
        $lineItems = $order->getLineItems()->toArray();
        array_walk(
            $lineItems,
            function (OrderLineItem $orderLineItem) use ($matchedPrices) {
                $productPriceCriteria = $this->createProductPriceCriteria($orderLineItem);
                if (!$productPriceCriteria) {
                    return;
                }

                $identifier = $productPriceCriteria->getIdentifier();
                if (array_key_exists($identifier, $matchedPrices)) {
                    $this->fillOrderLineItemData($orderLineItem, $matchedPrices[$identifier]);
                }
            }
        );
    }

    /**
     * @param Order $order
     */
    public function addMatchingPrices(Order $order)
    {
        $matchedPrices = $this->getMatchingPrices($order);

        $this->fillMatchingPrices($order, $matchedPrices);
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @param array $matchedPrice
     */
    protected function fillOrderLineItemData(OrderLineItem $orderLineItem, array $matchedPrice = [])
    {
        if (null === $orderLineItem->getCurrency() && !empty($matchedPrice['currency'])) {
            $orderLineItem->setCurrency((string)$matchedPrice['currency']);
        }
        if (null === $orderLineItem->getValue() && !empty($matchedPrice['value'])) {
            $orderLineItem->setValue((string)$matchedPrice['value']);
        }
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @return null|ProductPriceCriteria
     */
    protected function createProductPriceCriteria(OrderLineItem $orderLineItem)
    {
        $product = $orderLineItem->getProduct();
        $productUnit = $orderLineItem->getProductUnit();

        if (!$product || !$productUnit) {
            return null;
        }

        try {
            return new ProductPriceCriteria(
                $product,
                $productUnit,
                $orderLineItem->getQuantity(),
                $orderLineItem->getCurrency() ?: $orderLineItem->getOrder()->getCurrency()
            );
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}
