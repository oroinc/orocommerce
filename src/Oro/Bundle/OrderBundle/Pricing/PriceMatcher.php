<?php

namespace Oro\Bundle\OrderBundle\Pricing;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;

/**
 * Match prices by order line items.
 */
class PriceMatcher
{
    /** @var MatchingPriceProvider */
    protected $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    public function __construct(
        MatchingPriceProvider $provider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory
    ) {
        $this->provider = $provider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
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

        return $this->provider->getMatchingPrices(
            $lineItems->toArray(),
            $this->priceScopeCriteriaFactory->createByContext($order)
        );
    }

    public function fillMatchingPrices(Order $order, array $matchedPrices = [])
    {
        $lineItems = $order->getLineItems();

        $productsPriceCriteria = $this->productPriceCriteriaFactory->createListFromProductLineItems(
            $lineItems,
            $order->getCurrency()
        );

        foreach ($productsPriceCriteria as $lineItemIdx => $productPriceCriteria) {
            $identifier = $productPriceCriteria->getIdentifier();
            if (array_key_exists($identifier, $matchedPrices)) {
                $this->fillOrderLineItemData($lineItems->get($lineItemIdx), $matchedPrices[$identifier]);
            }
        }
    }

    public function addMatchingPrices(Order $order)
    {
        $matchedPrices = $this->getMatchingPrices($order);

        $this->fillMatchingPrices($order, $matchedPrices);
    }

    protected function fillOrderLineItemData(OrderLineItem $orderLineItem, array $matchedPrice = [])
    {
        if (null === $orderLineItem->getCurrency() && !empty($matchedPrice['currency'])) {
            $orderLineItem->setCurrency((string)$matchedPrice['currency']);
        }
        if (null === $orderLineItem->getValue() && !empty($matchedPrice['value'])) {
            $orderLineItem->setValue((string)$matchedPrice['value']);
        }
    }
}
