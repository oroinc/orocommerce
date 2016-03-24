<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\AbstractResultElement;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderTaxesListener
{
    /** @var TaxManager */
    protected $taxManager;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /**
     * @param TaxManager $taxManager
     * @param TaxationSettingsProvider $taxationSettingsProvider
     */
    public function __construct(TaxManager $taxManager, TaxationSettingsProvider $taxationSettingsProvider)
    {
        $this->taxManager = $taxManager;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $order = $event->getOrder();
        $data = $event->getData();

        $this->addMatchedPriceToOrderLineItems($order, $data);

        $result = $this->taxManager->getTax($order);
        $taxItems = array_map(
            function (Result $lineItem) {
                return [
                    'unit' => $lineItem->getUnit()->getArrayCopy(),
                    'row' => $lineItem->getRow()->getArrayCopy(),
                    'taxes' => array_map(
                        function (AbstractResultElement $item) {
                            return $item->getArrayCopy();
                        },
                        $lineItem->getTaxes()
                    ),
                ];
            },
            $result->getItems()
        );

        $data->offsetSet('taxItems', $taxItems);
    }

    /**
     * @param Order $order
     * @param \ArrayAccess $data
     */
    protected function addMatchedPriceToOrderLineItems(Order $order, \ArrayAccess $data)
    {
        if (!$data->offsetExists(MatchingPriceEventListener::MATCHED_PRICES_KEY)) {
            return;
        }

        $matchedPrices = $data->offsetGet(MatchingPriceEventListener::MATCHED_PRICES_KEY);
        if (!$matchedPrices) {
            return;
        }

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
     * @param OrderLineItem $orderLineItem
     * @param array $matchedPrice
     */
    protected function fillOrderLineItemData(OrderLineItem $orderLineItem, array $matchedPrice = [])
    {
        $hasChanges = false;

        if (null === $orderLineItem->getCurrency() && !empty($matchedPrice['currency'])) {
            $orderLineItem->setCurrency((string)$matchedPrice['currency']);

            $hasChanges = true;
        }
        if (null === $orderLineItem->getValue() && !empty($matchedPrice['value'])) {
            $orderLineItem->setValue((string)$matchedPrice['value']);

            $hasChanges = true;
        }

        if ($hasChanges) {
            $orderLineItem->postLoad();
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
