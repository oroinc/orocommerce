<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * This interface should be implemented entities to which can be applied promotion
 */
interface AppliedPromotionsAwareInterface
{
    /**
     * @param AppliedPromotion $promotion
     */
    public function addAppliedPromotion($promotion);

    /**
     * @return AppliedPromotion[]|Collection
     */
    public function getAppliedPromotions();

    /**
     * @param AppliedPromotion $promotion
     */
    public function removeAppliedPromotion($promotion);

    /**
     * @param AppliedPromotion[] $promotions
     * @return $this
     */
    public function setAppliedPromotions($promotions);
}
