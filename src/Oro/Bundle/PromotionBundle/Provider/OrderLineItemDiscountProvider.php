<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;
use Oro\Component\Math\BigDecimal;

/**
 * Provides discount breakdown for given order line item.
 * If taxation is enabled additionally provides row totals including and excluding taxes.
 */
class OrderLineItemDiscountProvider
{
    public function __construct(
        private readonly TaxationSettingsProvider $taxationSettingsProvider,
        private readonly TaxProviderRegistry $taxProviderRegistry,
        private readonly LineItemSubtotalProvider $lineItemSubtotalProvider,
        private readonly AppliedDiscountsProvider $appliedDiscountsProvider
    ) {
    }

    /**
     * @param OrderLineItem $orderLineItem
     *
     * @return array{
     *     appliedDiscountsAmount: string,
     *     currency: string,
     *     rowTotalAfterDiscountExcludingTax?: string,
     *     rowTotalAfterDiscountIncludingTax?: string,
     *     rowTotalAfterDiscount?: float
     * }
     */
    public function getOrderLineItemDiscount(OrderLineItem $orderLineItem): array
    {
        $discountAmount = $this->appliedDiscountsProvider->getDiscountsAmountByLineItem($orderLineItem);

        $isTaxationEnabled = $this->taxationSettingsProvider->isEnabled();
        if ($isTaxationEnabled) {
            return $this->getDiscountWithTaxes($discountAmount, $orderLineItem);
        }

        return $this->getDiscountsWithoutTaxes($discountAmount, $orderLineItem);
    }

    /**
     * @param float $discountAmount
     * @param OrderLineItem $lineItem
     *
     * @return array{
     *     appliedDiscountsAmount: string,
     *     rowTotalAfterDiscountExcludingTax: string,
     *     rowTotalAfterDiscountIncludingTax: string,
     *     currency: string
     * }
     */
    private function getDiscountWithTaxes(float $discountAmount, OrderLineItem $lineItem): array
    {
        $taxProvider = $this->taxProviderRegistry->getEnabledProvider();
        $taxResult = $taxProvider->getTax($lineItem)->getRow();

        $excludingTax = $taxResult->getExcludingTax() ?? '0.0';
        $includingTax = $taxResult->getIncludingTax() ?? '0.0';

        if (!$taxResult->isDiscountsIncluded()) {
            // Calculates using BigDecimal because a subtotal with included/excluded tax can be a big decimal.
            $excludingTax = (string)BigDecimal::of($excludingTax)->minus($discountAmount);
            $includingTax = (string)BigDecimal::of($includingTax)->minus($discountAmount);
        }

        return [
            'appliedDiscountsAmount' => (string) $discountAmount,
            'rowTotalAfterDiscountExcludingTax' => $excludingTax,
            'rowTotalAfterDiscountIncludingTax' => $includingTax,
            'currency' => $lineItem->getCurrency(),
        ];
    }

    /**
     * @param float $discountAmount
     * @param OrderLineItem $lineItem
     *
     * @return array{
     *     appliedDiscountsAmount: string,
     *     rowTotalAfterDiscount: string,
     *     currency: string
     * }
     */
    private function getDiscountsWithoutTaxes(float $discountAmount, OrderLineItem $lineItem): array
    {
        $rowTotal = $this->lineItemSubtotalProvider->getRowTotal($lineItem, $lineItem->getCurrency());
        $rowTotalWithoutDiscount = (string)BigDecimal::of($rowTotal)->minus($discountAmount);

        return [
            'appliedDiscountsAmount' => (string) $discountAmount,
            'rowTotalAfterDiscount' => $rowTotalWithoutDiscount,
            'currency' => $lineItem->getCurrency(),
        ];
    }
}
