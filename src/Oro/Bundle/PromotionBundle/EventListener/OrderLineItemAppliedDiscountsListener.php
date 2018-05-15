<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

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
        $excludingTax = $taxesRow->getExcludingTax() - $discountAmount;
        $includingTax = $taxesRow->getIncludingTax() - $discountAmount;

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscountExcludingTax' => $excludingTax,
            'rowTotalAfterDiscountIncludingTax' => $includingTax,
            'currency' => $lineItem->getCurrency(),
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

        return [
            'appliedDiscountsAmount' => $discountAmount,
            'rowTotalAfterDiscount' => $rowTotalWithoutDiscount - $discountAmount,
            'currency' => $lineItem->getCurrency(),
        ];
    }

    /**
     * @return TaxProviderInterface
     */
    private function getProvider()
    {
        return $this->taxProviderRegistry->getEnabledProvider();
    }
}
