<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * Blocks the automatic addition of discounts to the order if at least one item in the order has not been changed.
 */
class OrderPromotionDiscountsProviderDecorator implements PromotionDiscountsProviderInterface
{
    public function __construct(
        private PromotionDiscountsProviderInterface $baseDiscountsProvider,
        private PromotionAwareEntityHelper $promotionAwareHelper,
    ) {
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

        if ($this->isSupport($sourceEntity)) {
            $appliedPromotionsIds = array_map(
                fn (AppliedPromotion $promotion) => $promotion->getSourcePromotionId(),
                $sourceEntity->getAppliedPromotions()->toArray()
            );

            $discounts = array_filter(
                $discounts,
                function (DiscountInterface $discount) use ($appliedPromotionsIds) {
                    // Coupons are not added automatically, so you don't need to filter them.
                    if (!$discount->getPromotion()->isUseCoupons()) {
                        return in_array($discount->getPromotion()->getId(), $appliedPromotionsIds);
                    }

                    return true;
                }
            );
        }

        return $discounts;
    }

    /**
     * @param object $sourceEntity
     *
     * @return bool
     */
    private function isSupport($sourceEntity): bool
    {
        /** @var Order $sourceEntity */
        if ($this->isOrder($sourceEntity)) {
            /** @var PersistentCollection $lineItems */
            $lineItems = $sourceEntity->getLineItems();

            /**
             * @var OrderLineItem $lineItem
             *
             * Need in case a new product has not been added but an existing one has been changed.
             */
            foreach ($lineItems as $lineItem) {
                if ($lineItem->getProduct() && $lineItem->getProductSku() !== $lineItem->getProduct()->getSku()) {
                    return false;
                }
            }

            // Need in case a new product is added to the order.
            return !$lineItems->isDirty();
        }

        return false;
    }

    /**
     * @param object $sourceEntity
     *
     * @return bool
     */
    private function isOrder($sourceEntity): bool
    {
        return
            $sourceEntity instanceof Order
            && $this->promotionAwareHelper->isPromotionAware($sourceEntity)
            && $sourceEntity->getId();
    }
}
