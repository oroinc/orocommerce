<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\Converter\ConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
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
        $subtotalDiscounts = $discountContext->getSubtotalDiscounts();
        $promotions = $discountContext->getPromotions();
        if ($subtotalDiscounts && $promotions) {
            foreach ($subtotalDiscounts as $discount) {
                $discountType = $discount->getDiscountType();
                $promotion = $promotions[$discountType];

                $appliedDiscounts[] = (new AppliedDiscount())
                    ->setOrder($order)
                    ->setType($discountType)
                    ->setAmount($discount->getDiscountValue())
                    ->setConfigOptions($promotion->getDiscountConfiguration()->getOptions())
                    ->setPromotion($promotion);
            }
        }
        return $appliedDiscounts;
    }
}
