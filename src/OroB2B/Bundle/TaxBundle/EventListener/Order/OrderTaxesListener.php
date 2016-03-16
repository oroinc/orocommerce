<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class OrderTaxesListener
{
    /** @var TaxManager */
    protected $taxManager;

    /** @var NumberFormatter */
    protected $numberFormatter;

    /**
     * @param TaxManager $taxManager
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(TaxManager $taxManager, NumberFormatter $numberFormatter)
    {
        $this->taxManager = $taxManager;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();
        $result = $this->taxManager->getTax($order);
        $taxesItems = [];

        /** @var Result $lineItem */
        foreach ($result->getItems() as $lineItem) {
            $taxesItem = [
                'unit' => $lineItem->getUnit()->getArrayCopy(),
                'row' => $lineItem->getRow()->getArrayCopy()
            ];

            $itemTaxes = $lineItem->getTaxes();
            if (!empty($itemTaxes)) {
                $taxes = [];
                /** @var TaxResultElement $itemTax */
                foreach ($itemTaxes as $itemTax) {
                    $tax = $itemTax->getArrayCopy();
                    $tax['rate'] = $this->numberFormatter->formatPercent($itemTax->getRate());
                    $taxes[] = $tax;
                }
                $taxesItem['taxes'] = $taxes;
            }
            $taxesItems[] = $taxesItem;
        }
        $event->getData()->offsetSet('taxesItems', $taxesItems);
    }
}
