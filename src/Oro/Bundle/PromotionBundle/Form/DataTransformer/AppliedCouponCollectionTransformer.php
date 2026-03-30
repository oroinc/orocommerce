<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms applied coupon collection by sorting it by promotion sort order.
 */
class AppliedCouponCollectionTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform($value): mixed
    {
        if (!$value instanceof Collection || $value->count() === 0) {
            return $value;
        }

        $appliedCouponsArray = $value->toArray();
        // uasort() is used on purpose as the transformer must maintain the collection keys.
        uasort($appliedCouponsArray, $this->sortBySortOrder(...));

        return new ArrayCollection($appliedCouponsArray);
    }

    private function sortBySortOrder(AppliedCoupon $a, AppliedCoupon $b): int
    {
        $aPromotion = $a->getAppliedPromotion();
        $bPromotion = $b->getAppliedPromotion();

        if (!$aPromotion || !$bPromotion) {
            return 0;
        }

        $aData = $aPromotion->getPromotionData();
        $bData = $bPromotion->getPromotionData();

        $aSortOrder = $aData['rule']['sortOrder'] ?? 0;
        $bSortOrder = $bData['rule']['sortOrder'] ?? 0;

        return $aSortOrder <=> $bSortOrder;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        return $value;
    }
}
