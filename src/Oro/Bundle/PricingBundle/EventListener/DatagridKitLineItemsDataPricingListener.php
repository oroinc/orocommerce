<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;

/**
 * Adds product kit line items pricing data.
 */
class DatagridKitLineItemsDataPricingListener
{
    private NumberFormatter $numberFormatter;

    public function __construct(NumberFormatter $numberFormatter)
    {
        $this->numberFormatter = $numberFormatter;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();

        foreach ($lineItems as $lineItem) {
            $lineItemData = $event->getDataForLineItem($lineItem->getEntityIdentifier());
            if (empty($lineItemData[DatagridKitLineItemsDataListener::IS_KIT])) {
                continue;
            }

            $currency = $lineItemData[DatagridLineItemsDataPricingListener::CURRENCY] ?? '';
            if (empty($currency)) {
                continue;
            }

            $priceValue = (float)($lineItemData[DatagridLineItemsDataPricingListener::PRICE_VALUE] ?? 0);
            foreach ($lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] ?? [] as $kitItemLineItemData) {
                if (empty($kitItemLineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE])) {
                    $event->addDataForLineItem(
                        $lineItem->getEntityIdentifier(),
                        [
                            DatagridLineItemsDataPricingListener::PRICE_VALUE => null,
                            DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => null,
                            DatagridLineItemsDataPricingListener::PRICE => null,
                            DatagridLineItemsDataPricingListener::SUBTOTAL => null,
                        ]
                    );
                    continue 2;
                }

                $priceValue = $this->sumValuesAsBigDecimal(
                    $priceValue,
                    (float)$kitItemLineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE]
                );
            }

            $subtotalValue = $priceValue * (float)$lineItem->getQuantity();

            $event->addDataForLineItem(
                $lineItem->getEntityIdentifier(),
                [
                    'priceValue' => $priceValue,
                    'subtotalValue' => $priceValue * (float)$lineItem->getQuantity(),
                    'price' => $this->numberFormatter->formatCurrency($priceValue, $currency),
                    'subtotal' => $this->numberFormatter->formatCurrency($subtotalValue, $currency),
                ]
            );
        }
    }

    private function sumValuesAsBigDecimal(float $valueOne, float $valueTwo): float
    {
        return BigDecimal::of($valueOne)
            ->plus(BigDecimal::of($valueTwo))
            ->toFloat();
    }
}
