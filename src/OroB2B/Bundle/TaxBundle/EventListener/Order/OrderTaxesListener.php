<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\AbstractResultElement;
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

        $event->getData()->offsetSet('taxItems', $taxItems);
        $event->getData()->offsetSet('taxItems', $taxItems);
    }
}
