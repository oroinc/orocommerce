<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;

class AppliedPromotionsAwareStub
{
    /**
     * @param AppliedPromotion $promotion
     */
    public function addAppliedPromotion($promotion)
    {
    }

    /**
     * @return AppliedPromotion[]|Collection
     */
    public function getAppliedPromotions()
    {
    }

    /**
     * @param AppliedPromotion $promotion
     */
    public function removeAppliedPromotion($promotion)
    {
    }

    /**
     * @param AppliedPromotion[] $promotions
     * @return $this
     */
    public function setAppliedPromotions($promotions)
    {
    }
}
