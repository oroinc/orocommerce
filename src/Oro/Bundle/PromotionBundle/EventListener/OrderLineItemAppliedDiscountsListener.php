<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

/**
 * Gets discounts and taxes for the order
 */
class OrderLineItemAppliedDiscountsListener
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private TaxProviderRegistry $taxProviderRegistry,
        private TaxationSettingsProvider $taxationSettingsProvider,
        private LineItemSubtotalProvider $lineItemSubtotalProvider,
        private AppliedDiscountsProvider $appliedDiscountsProvider
    ) {
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $order = $event->getEntity();
        $isTaxationEnabled = $this->taxationSettingsProvider->isEnabled();

        $discounts = [];
        foreach ($order->getLineItems() as $lineItem) {
            $discountAmount = $this->appliedDiscountsProvider->getDiscountsAmountByLineItem($lineItem);
            if ($isTaxationEnabled) {
                $discounts[] = $this->getDiscountWithTaxes($discountAmount, $lineItem);
            } else {
                $discounts[] = $this->getDiscountsWithoutTaxes($discountAmount, $lineItem);
            }
        }
        $event->getData()->offsetSet('appliedDiscounts', $discounts);
    }

    private function getDiscountWithTaxes(float $discountAmount, OrderLineItem $lineItem): array
    {
        $taxesRow = $this->getProvider()->getTax($lineItem)->getRow();

        $excludingTax = $taxesRow->getExcludingTax() ?? '0.0';
        $includingTax = $taxesRow->getIncludingTax() ?? '0.0';

        if (!$taxesRow->isDiscountsIncluded()) {
            // Calculates using BigDecimal because subtotal with included/excluded tax can be a big decimal.
            $excludingTax = (string) BigDecimal::of($excludingTax)->minus($discountAmount);
            $includingTax = (string) BigDecimal::of($includingTax)->minus($discountAmount);
        }

        $currency = $this->getLineItemCurrency($lineItem);

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscountExcludingTax' => $excludingTax,
            'rowTotalAfterDiscountIncludingTax' => $includingTax,
            'currency' => $currency,
        ];
    }

    private function getDiscountsWithoutTaxes(float $discountAmount, OrderLineItem $lineItem): array
    {
        $rowTotalWithoutDiscount = $this->lineItemSubtotalProvider->getRowTotal($lineItem, $lineItem->getCurrency());
        $currency = $this->getLineItemCurrency($lineItem);

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscount' => $rowTotalWithoutDiscount - $discountAmount,
            'currency' => $currency,
        ];
    }

    private function getLineItemCurrency(OrderLineItem $lineItem): string
    {
        return $lineItem->getOrder() && $lineItem->getOrder()->getCurrency()
            ? $lineItem->getOrder()->getCurrency()
            : $lineItem->getCurrency();
    }

    private function getProvider(): TaxProviderInterface
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
