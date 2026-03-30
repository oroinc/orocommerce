<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrderBundle\Event\OrderDuplicateAfterEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

/**
 * Explicitly duplicates applied promotions collection after an order is duplicated.
 * This is required because the duplicator component does not support extended entity fields.
 */
class OrderDuplicateAfterEventListener
{
    public function onOrderDuplicateAfter(OrderDuplicateAfterEvent $event): void
    {
        $order = $event->getOrder();
        $duplicatedOrder = $event->getDuplicatedOrder();

        /** @var AppliedPromotion $appliedPromotion */
        foreach ($order->getAppliedPromotions() as $appliedPromotion) {
            $clonedAppliedPromotion = $this->createSameInstance($appliedPromotion);
            $clonedAppliedPromotion->setActive($appliedPromotion->isActive());
            $clonedAppliedPromotion->setRemoved($appliedPromotion->isRemoved());
            $clonedAppliedPromotion->setType($appliedPromotion->getType());
            $clonedAppliedPromotion->setSourcePromotionId($appliedPromotion->getSourcePromotionId());
            $clonedAppliedPromotion->setPromotionName($appliedPromotion->getPromotionName());
            $clonedAppliedPromotion->setConfigOptions($appliedPromotion->getConfigOptions());
            $clonedAppliedPromotion->setPromotionData($appliedPromotion->getPromotionData());
            $clonedAppliedPromotion->setOrder($duplicatedOrder);

            foreach ($appliedPromotion->getAppliedDiscounts() as $appliedDiscount) {
                $clonedAppliedDiscount = $this->createSameInstance($appliedDiscount);
                $clonedAppliedDiscount->setAmount((float)$appliedDiscount->getAmount());
                $clonedAppliedDiscount->setCurrency($appliedDiscount->getCurrency());

                if ($appliedDiscount->getLineItem()) {
                    $lineItemIndex = $order->getLineItems()->indexOf($appliedDiscount->getLineItem());

                    if ($lineItemIndex !== false) {
                        $clonedLineItem = $duplicatedOrder->getLineItems()->get($lineItemIndex);
                        $clonedAppliedDiscount->setLineItem($clonedLineItem);
                    }
                }

                $clonedAppliedPromotion->addAppliedDiscount($clonedAppliedDiscount);
            }

            $appliedCoupon = $appliedPromotion->getAppliedCoupon();
            if ($appliedCoupon) {
                $clonedAppliedCoupon = $this->createSameInstance($appliedCoupon);
                $clonedAppliedCoupon->setCouponCode($appliedCoupon->getCouponCode());
                $clonedAppliedCoupon->setSourcePromotionId($appliedCoupon->getSourcePromotionId());
                $clonedAppliedCoupon->setSourceCouponId($appliedCoupon->getSourceCouponId());
                $clonedAppliedCoupon->setOrder($duplicatedOrder);

                $clonedAppliedPromotion->setAppliedCoupon($clonedAppliedCoupon);
                $duplicatedOrder->addAppliedCoupon($clonedAppliedCoupon);
            }

            $duplicatedOrder->addAppliedPromotion($clonedAppliedPromotion);
        }
    }

    /**
     * Creates an instance of the same class as the specified entity is.
     */
    private function createSameInstance(object $object): object
    {
        return new (ClassUtils::getClass($object));
    }
}
