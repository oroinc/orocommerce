<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfterListenerInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Pricing\OrderLineItemIsPriceOverriddenCalculator;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;

/**
 * Adds isPriceOverridden flag and tier prices data to order line items datagrid records.
 */
final class OrderLineItemDraftIsPriceOverriddenDatagridListener implements OrmResultAfterListenerInterface
{
    public function __construct(
        private readonly OrderLineItemTierPricesProvider $orderLineItemTierPricesProvider,
        private readonly OrderLineItemIsPriceOverriddenCalculator $isPriceOverriddenCalculator,
    ) {
    }

    #[\Override]
    public function onResultAfter(OrmResultAfter $event): void
    {
        /** @var ResultRecordInterface[] $records */
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        // First pass – collect line items that are eligible for price look-up.
        $lineItems = [];
        foreach ($records as $key => $record) {
            $lineItem = $record->getRootEntity();
            if ($lineItem instanceof OrderLineItem && $lineItem->getProduct() !== null) {
                $lineItems[$key] = $lineItem;
            }
        }

        // Single price-storage query for every eligible line item at once.
        $batchTierPrices = $lineItems
            ? $this->orderLineItemTierPricesProvider->getTierPricesForLineItems($lineItems)
            : [];

        // Second pass – distribute the fetched prices to each record.
        foreach ($records as $key => $record) {
            $this->populateRecord($record, $batchTierPrices[$key] ?? []);
        }
    }

    /**
     * @param ResultRecordInterface $record
     * @param list<ProductPriceInterface> $tierPrices
     */
    private function populateRecord(
        ResultRecordInterface $record,
        array $tierPrices
    ): void {
        $lineItem = $record->getRootEntity();

        if (!$lineItem instanceof OrderLineItem || $lineItem->getProduct() === null) {
            $record->setValue('tierPrices', []);
            $record->setValue('isPriceOverridden', false);

            return;
        }

        $record->setValue('tierPrices', $this->serializeTierPrices($tierPrices));
        $record->setValue(
            'isPriceOverridden',
            $this->isPriceOverriddenCalculator->isOverridden($lineItem, $tierPrices)
        );
    }

    /**
     * @param list<ProductPriceInterface> $tierPrices
     */
    private function serializeTierPrices(array $tierPrices): array
    {
        return array_values(array_map(
            static fn (ProductPriceInterface $price) => [
                'price' => $price->getPrice()->getValue(),
                'currency' => $price->getPrice()->getCurrency(),
                'quantity' => $price->getQuantity(),
                'unit' => $price->getUnit()->getCode(),
            ],
            $tierPrices
        ));
    }
}
