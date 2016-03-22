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

        $order->getLineItems()->map(
            function (OrderLineItem $orderLineItem) use ($matchedPrices) {
                $productPriceCriteria = new ProductPriceCriteria(
                    $orderLineItem->getProduct(),
                    $orderLineItem->getProductUnit(),
                    $orderLineItem->getQuantity(),
                    $orderLineItem->getCurrency() ?: $orderLineItem->getOrder()->getCurrency()
                );

                $identifier = $productPriceCriteria->getIdentifier();
                if (array_key_exists($identifier, $matchedPrices)) {
                    $hasChanges = false;

                    if (!empty($matchedPrices[$identifier]['currency'])) {
                        $orderLineItem->setCurrency((string)$matchedPrices[$identifier]['currency']);

                        $hasChanges = true;
                    }
                    if (!empty($matchedPrices[$identifier]['value'])) {
                        $orderLineItem->setValue((string)$matchedPrices[$identifier]['value']);

                        $hasChanges = true;
                    }

                    if ($hasChanges) {
                        $orderLineItem->postLoad();
                    }
                }
            }
        );
    }
}
