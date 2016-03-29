<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher;
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

    /** @var PriceMatcher */
    protected $priceMatcher;

    /**
     * @param TaxManager $taxManager
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param PriceMatcher $priceMatcher
     */
    public function __construct(
        TaxManager $taxManager,
        TaxationSettingsProvider $taxationSettingsProvider,
        PriceMatcher $priceMatcher
    ) {
        $this->taxManager = $taxManager;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->priceMatcher = $priceMatcher;
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

        $this->priceMatcher->fillMatchingPrices($order, $matchedPrices);
    }
}
