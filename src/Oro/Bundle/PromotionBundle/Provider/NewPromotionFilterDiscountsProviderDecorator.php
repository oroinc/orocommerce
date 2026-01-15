<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Decorator that disables discounts from promotions created or updated after the order was placed.
 * This prevents new or modified promotions from affecting already placed orders.
 */
class NewPromotionFilterDiscountsProviderDecorator implements PromotionDiscountsProviderInterface
{
    public function __construct(private PromotionDiscountsProviderInterface $baseDiscountsProvider)
    {
    }

    #[\Override]
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);

        if (!$this->isSupportedOrder($sourceEntity)) {
            return $discounts;
        }

        $orderCreatedAt = $sourceEntity->getCreatedAt();
        if ($orderCreatedAt === null) {
            return $discounts;
        }

        $appliedPromotionIds = $this->getAppliedPromotionIds($sourceEntity);

        foreach ($discounts as $key => $discount) {
            $promotion = $discount->getPromotion();
            $promotionId = $promotion->getId();

            // Skip already applied promotions (including manually added by admin)
            if (in_array($promotionId, $appliedPromotionIds, true)) {
                continue;
            }

            // Skip coupon-based promotions (they require manual coupon code entry)
            if ($promotion->isUseCoupons()) {
                continue;
            }

            // Remove promotions created or updated after the order was placed
            $promotionUpdatedAt = $promotion instanceof Promotion ? $promotion->getUpdatedAt() : null;

            if ($promotionUpdatedAt !== null && $promotionUpdatedAt > $orderCreatedAt) {
                $discounts[$key] = new DisabledDiscountDecorator($discount);
            }
        }

        return array_values($discounts);
    }

    private function getAppliedPromotionIds(Order $order): array
    {
        $appliedPromotions = $order->getAppliedPromotions()->toArray();
        $ids = [];
        /** @var AppliedPromotion $appliedPromotion */
        foreach ($appliedPromotions as $appliedPromotion) {
            $ids[] = $appliedPromotion->getSourcePromotionId();
        }

        return $ids;
    }

    private function isSupportedOrder(object $sourceEntity): bool
    {
        if (!$sourceEntity instanceof Order) {
            return false;
        }

        return true;
    }
}
