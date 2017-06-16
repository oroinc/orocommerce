<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;

class ApplyAllStrategy implements StrategyInterface
{
    /**
     * @var SubtotalProviderInterface
     */
    private $lineItemsSubtotalProvider;

    /**
     * @param SubtotalProviderInterface $lineItemsSubtotalProvider
     */
    public function __construct(SubtotalProviderInterface $lineItemsSubtotalProvider)
    {
        $this->lineItemsSubtotalProvider = $lineItemsSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.promotion.discount.strategy.apply_all.label';
    }

    /**
     * {@inheritdoc}
     */
    public function process(DiscountContext $discountContext, array $discounts): DiscountContext
    {
        foreach ($discounts as $discount) {
            $discount->apply($discountContext);
        }

        $this->processLineItemDiscounts($discountContext);

        $subtotal = $this->lineItemsSubtotalProvider->getSubtotal($discountContext);
        $discountContext->setSubtotal($subtotal->getAmount());

        $this->processTotalDiscounts($discountContext);
        $this->processShippingDiscounts($discountContext);

        return $discountContext;
    }

    /**
     * @param DiscountContext $discountContext
     */
    private function processLineItemDiscounts(DiscountContext $discountContext)
    {
        foreach ($discountContext->getLineItems() as $discountLineItem) {
            foreach ($discountLineItem->getDiscounts() as $discount) {
                $discountAmount = $discount->calculate($discountLineItem);
                $discountLineItem->addDiscountInformation(new DiscountInformation($discount, $discountAmount));

                $subtotal = $this->getSubtotalWithDiscount($discountLineItem->getSubtotal(), $discountAmount);
                $discountLineItem->setSubtotal($subtotal);
            }
        }
    }

    /**
     * @param DiscountContext $discountContext
     */
    private function processTotalDiscounts(DiscountContext $discountContext)
    {
        foreach ($discountContext->getSubtotalDiscounts() as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addSubtotalDiscountInformation(new DiscountInformation($discount, $discountAmount));

            $subtotal = $this->getSubtotalWithDiscount($discountContext->getSubtotal(), $discountAmount);
            $discountContext->setSubtotal($subtotal);
        }
    }

    /**
     * @param DiscountContext $discountContext
     */
    private function processShippingDiscounts(DiscountContext $discountContext)
    {
        foreach ($discountContext->getShippingDiscounts() as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addShippingDiscountInformation(new DiscountInformation($discount, $discountAmount));

            $subtotal = $this->getSubtotalWithDiscount($discountContext->getShippingCost(), $discountAmount);
            $discountContext->setShippingCost($subtotal);
        }
    }

    /**
     * @param float $subtotal
     * @param float $discountAmount
     * @return float
     */
    private function getSubtotalWithDiscount($subtotal, $discountAmount): float
    {
        $subtotal -= $discountAmount;
        if ($subtotal < 0.0) {
            return 0.0;
        }

        return $subtotal;
    }
}
