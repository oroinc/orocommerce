<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemAppliedDiscountsListener
{
    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var DiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @param TaxManager $taxManager
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param DiscountsProvider $discountsProvider
     */
    public function __construct(
        TaxManager $taxManager,
        TaxationSettingsProvider $taxationSettingsProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        DiscountsProvider $discountsProvider
    ) {
        $this->taxManager = $taxManager;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->discountsProvider = $discountsProvider;
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
            $discountAmount = $this->discountsProvider->getDiscountsAmountByLineItem($lineItem);
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
        $taxesRow = $this->taxManager->getTax($lineItem)->getRow();
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
}
