<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
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

    #[\Override]
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);
        if ($sourceEntity instanceof Order && $this->isSupportedOrder($sourceEntity)) {
            $appliedPromotionsIds = $this->getAppliedPromotionsIds($sourceEntity);
            $filteredDiscounts = [];
            foreach ($discounts as $discount) {
                $promotion = $discount->getPromotion();
                // coupons are not added automatically, so such discounts should not be filtered out
                if ($promotion->isUseCoupons() || isset($appliedPromotionsIds[$promotion->getId()])) {
                    $filteredDiscounts[] = $discount;
                }
            }

            return $filteredDiscounts;
        }

        return $discounts;
    }

    private function isSupportedOrder(Order $sourceEntity): bool
    {
        if (!$sourceEntity->getId() || !$this->promotionAwareHelper->isPromotionAware($sourceEntity)) {
            return false;
        }

        /** @var PersistentCollection $lineItems */
        $lineItems = $sourceEntity->getLineItems();

        // Need in case a new product has not been added but an existing one has been changed.
        /** @var OrderLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getProduct() && $lineItem->getProductSku() !== $lineItem->getProduct()->getSku()) {
                return false;
            }
        }

        // Need in case a new product is added to the order.
        return !$lineItems->isDirty();
    }

    private function getAppliedPromotionsIds(Order $sourceEntity): array
    {
        $appliedPromotionsIds = [];
        $appliedPromotions = $sourceEntity->getAppliedPromotions()->toArray();
        foreach ($appliedPromotions as $appliedPromotion) {
            $appliedPromotionsIds[$appliedPromotion->getSourcePromotionId()] = true;
        }

        return $appliedPromotionsIds;
    }
}
