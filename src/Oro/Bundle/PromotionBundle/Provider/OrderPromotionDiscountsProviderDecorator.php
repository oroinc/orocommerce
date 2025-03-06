<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;

/**
 * Decorator that disable discounts by removed promotions to correct recalculation order discounts on backend
 */
class OrderPromotionDiscountsProviderDecorator implements PromotionDiscountsProviderInterface
{
    /**
     * @var PromotionDiscountsProviderInterface
     */
    private $baseDiscountsProvider;

    public function __construct(PromotionDiscountsProviderInterface $baseDiscountsProvider)
    {
        $this->baseDiscountsProvider = $baseDiscountsProvider;
    }

    /**
     * @param object $sourceEntity
     * @param DiscountContextInterface $context
     *
     * @return array
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);

        if ($this->isSupportedOrder($sourceEntity)) {
            $appliedPromotions = $this->getOrderAppliedPromotionRemovedMap($sourceEntity);

            foreach ($discounts as $key => $discount) {
                $promotionId = $discount->getPromotion()->getId();
                $exists = array_key_exists($discount->getPromotion()->getId(), $appliedPromotions);

                if ($exists && $appliedPromotions[$promotionId] === true) {
                    $discounts[$key] = new DisabledDiscountDecorator($discount);
                }
            }
        }

        return $discounts;
    }

    private function getOrderAppliedPromotionRemovedMap(Order $order): array
    {
        $appliedPromotions = $order->getAppliedPromotions()->toArray();
        $appliedPromotionsMap = [];
        /** @var AppliedPromotion $appliedPromotion */
        foreach ($appliedPromotions as $appliedPromotion) {
            $appliedPromotionsMap[$appliedPromotion->getSourcePromotionId()] = $appliedPromotion->isRemoved();
        }

        return $appliedPromotionsMap;
    }

    /**
     * @param object $sourceEntity
     *
     * @return bool
     */
    private function isSupportedOrder($sourceEntity): bool
    {
        return
            $sourceEntity instanceof Order
            && $sourceEntity instanceof AppliedPromotionsAwareInterface;
    }
}
