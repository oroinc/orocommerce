<?php

namespace Oro\Bundle\TaxBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Adds "taxItems" to the order entry point data.
 */
class OrderTaxesListener
{
    public const TAX_ITEMS = 'taxItems';

    private TaxProviderRegistry $taxProviderRegistry;

    private TaxationSettingsProvider $taxationSettingsProvider;

    public function __construct(
        TaxProviderRegistry $taxProviderRegistry,
        TaxationSettingsProvider $taxationSettingsProvider
    ) {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $result = $this->taxProviderRegistry
            ->getEnabledProvider()
            ->getTax($event->getOrder());

        $taxItems = array_map(
            static fn (Result $lineItem) => [
                'unit' => $lineItem->getUnit()->getArrayCopy(),
                'row' => $lineItem->getRow()->getArrayCopy(),
                'taxes' => array_map(
                    static fn (AbstractResultElement $item) => $item->getArrayCopy(),
                    $lineItem->getTaxes()
                ),
            ],
            $result->getItems()
        );

        $event
            ->getData()
            ->offsetSet(self::TAX_ITEMS, $taxItems);
    }
}
