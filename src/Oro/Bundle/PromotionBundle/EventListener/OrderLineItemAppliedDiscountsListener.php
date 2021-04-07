<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Brick\Math\BigDecimal;
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
    /**
     * @var TaxProviderRegistry
     */
    protected $taxProviderRegistry;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var AppliedDiscountsProvider
     */
    protected $appliedDiscountsProvider;

    /**
     * @param TaxProviderRegistry $taxProviderRegistry
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param AppliedDiscountsProvider $appliedDiscountsProvider
     */
    public function __construct(
        TaxProviderRegistry $taxProviderRegistry,
        TaxationSettingsProvider $taxationSettingsProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        AppliedDiscountsProvider $appliedDiscountsProvider
    ) {
        $this->taxProviderRegistry = $taxProviderRegistry;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->appliedDiscountsProvider = $appliedDiscountsProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
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

    /**
     * @param float $discountAmount
     * @param OrderLineItem $lineItem
     * @return array
     */
    protected function getDiscountWithTaxes(float $discountAmount, OrderLineItem $lineItem)
    {
        $taxesRow = $this->getProvider()->getTax($lineItem)->getRow();

        if ($taxesRow->isDiscountsIncluded()) {
            $excludingTax = $taxesRow->getExcludingTax();
            $includingTax = $taxesRow->getIncludingTax();
        } else {
            // Calculates using BigDecimal because subtotal with included/excluded tax can be a big decimal.
            $excludingTax = (string) BigDecimal::of($taxesRow->getExcludingTax())->minus($discountAmount);
            $includingTax = (string) BigDecimal::of($taxesRow->getIncludingTax())->minus($discountAmount);
        }

        $currency = $this->getLineItemCurrency($lineItem);

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscountExcludingTax' => $excludingTax,
            'rowTotalAfterDiscountIncludingTax' => $includingTax,
            'currency' => $currency,
        ];
    }

    /**
     * @param float $discountAmount
     * @param OrderLineItem $lineItem
     * @return array
     */
    protected function getDiscountsWithoutTaxes(float $discountAmount, OrderLineItem $lineItem)
    {
        $rowTotalWithoutDiscount = $this->lineItemSubtotalProvider->getRowTotal($lineItem, $lineItem->getCurrency());
        $currency = $this->getLineItemCurrency($lineItem);

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscount' => $rowTotalWithoutDiscount - $discountAmount,
            'currency' => $currency,
        ];
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return string
     */
    private function getLineItemCurrency(OrderLineItem $lineItem): string
    {
        return $lineItem->getOrder() && $lineItem->getOrder()->getCurrency()
            ? $lineItem->getOrder()->getCurrency()
            : $lineItem->getCurrency();
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
