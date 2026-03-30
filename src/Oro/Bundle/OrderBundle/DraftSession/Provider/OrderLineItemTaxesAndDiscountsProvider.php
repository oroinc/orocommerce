<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Provider\OrderLineItemDiscountProvider;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Provides tax and discount data for a given order line item.
 */
class OrderLineItemTaxesAndDiscountsProvider
{
    public function __construct(
        private readonly TaxationSettingsProvider $taxationSettingsProvider,
        private readonly TaxProviderRegistry $taxProviderRegistry,
        private readonly OrderLineItemDiscountProvider $orderLineItemDiscountProvider,
    ) {
    }

    /**
     * Returns tax breakdown for the given line item, or null if taxation is disabled.
     *
     * @return array{unit: array, row: array, taxes: array}|null
     */
    public function getLineItemTaxes(OrderLineItem $orderLineItem): ?array
    {
        if ($this->taxationSettingsProvider->isDisabled()) {
            return null;
        }

        $order = $orderLineItem->getOrder();
        $lineItemIndex = $order->getLineItems()->indexOf($orderLineItem);
        $orderTaxResult = $this->taxProviderRegistry->getEnabledProvider()->getTax($order);
        $taxResult = $orderTaxResult->getItems()[$lineItemIndex] ?? null;
        if ($taxResult === null) {
            return null;
        }

        return [
            'unit' => $taxResult->getUnit()->getArrayCopy(),
            'row' => $taxResult->getRow()->getArrayCopy(),
            'taxes' => array_map(
                static fn (AbstractResultElement $item) => $item->getArrayCopy(),
                $taxResult->getTaxes()
            ),
        ];
    }

    /**
     * Returns discount data for the given line item.
     */
    public function getLineItemDiscounts(OrderLineItem $orderLineItem): array
    {
        return $this->orderLineItemDiscountProvider->getOrderLineItemDiscount($orderLineItem);
    }
}
