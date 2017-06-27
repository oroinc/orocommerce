<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\Converter\ConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;

class AppliedDiscountManager
{
    /** @var PromotionExecutor */
    protected $promotionExecutor;

    /** @var  ConverterInterface */
    protected $appliedDiscountConverter;

    /**
     * @param PromotionExecutor $promotionExecutor
     * @param ConverterInterface $appliedDiscountConverter
     */
    public function __construct(
        PromotionExecutor $promotionExecutor,
        ConverterInterface $appliedDiscountConverter
    ) {
        $this->promotionExecutor = $promotionExecutor;
        $this->appliedDiscountConverter = $appliedDiscountConverter;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getAppliedDiscounts(Order $order): array
    {
        $appliedDiscounts = [];
        $discountContext = $this->promotionExecutor->execute($order);
        /** @var DiscountInterface[] $discountsData */
        $discountsData = array_merge(
            $discountContext->getSubtotalDiscounts(),
            $discountContext->getShippingDiscounts()
        );
        if ($discountsData) {
            foreach ($discountsData as $discount) {
                $appliedDiscounts[] = $this->createAppliedDiscount($order, $discount);
            }
        }

        return array_merge($appliedDiscounts, $this->getLineItemDiscounts($order, $discountContext->getLineItems()));
    }

    /**
     * @param Order $order
     * @param DiscountInterface $discount
     * @param array $options
     * @return AppliedDiscount
     */
    public function createAppliedDiscount(
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
