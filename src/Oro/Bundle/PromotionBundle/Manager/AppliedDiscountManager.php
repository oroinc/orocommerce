<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class AppliedDiscountManager
{
    /** @var PromotionExecutor */
    protected $promotionExecutor;

    /**
     * @param PromotionExecutor $promotionExecutor
     */
    public function setPromotionExecutor(PromotionExecutor $promotionExecutor)
    {
        $this->promotionExecutor = $promotionExecutor;
    }
    /**
     * @param Order $order
     * @return array|null
     */
    public function getAppliedDiscounts(Order $order)
    {
        $discountContext = $this->promotionExecutor->execute($order);
        /** @var DiscountInterface[] $discountsData */
        $discountsData = array_merge(
            $discountContext->getSubtotalDiscounts(),
            $discountContext->getShippingDiscounts()
        );
        if ($discountsData) {
            $appliedDiscounts = [];
            foreach ($discountsData as $discount) {
                $appliedDiscounts[] = $this->createAppliedDiscount($order, $discount);
            }
            return array_merge(
                $appliedDiscounts,
                $this->getLineItemDiscounts($order, $discountContext->getLineItems())
            );
        }

        return null;
    }

    /**
     * @param Order $order
     * @param DiscountInterface $discount
     * @param array $options
     * @return AppliedDiscount
     */
    protected function createAppliedDiscount(
        Order $order,
        DiscountInterface $discount,
        array $options = []
    ): AppliedDiscount {
        $promotion = $discount->getPromotion();
        if (!$promotion) {
            throw new \LogicException('required parameter "promotion" of discount is missing');
        }

        return (new AppliedDiscount())
            ->setOrder($order)
            ->setType($discount->getDiscountType())
            ->setAmount($discount->getDiscountValue())
            ->setCurrency($discount->getDiscountCurrency())
            ->setConfigOptions($promotion->getDiscountConfiguration()->getOptions())
            ->setOptions($options)
            ->setPromotion($promotion);
    }

    /**
     * @param Order $order
     * @param array $discountLineItems
     * @return array
     */
    protected function getLineItemDiscounts(Order $order, array $discountLineItems)
    {
        $appliedDiscountsData = [];
        /** @var DiscountLineItem $discountLineItem */
        foreach ($discountLineItems as $discountLineItem) {
            $configOptions = ['sourceEntityId' => $discountLineItem->getSourceLineItem()->getId()];
            foreach ($discountLineItem->getDiscounts() as $discount) {
                $appliedDiscountsData[] = $this->createAppliedDiscount($order, $discount, $configOptions);
            }
        }

        return $appliedDiscountsData;
    }
}
