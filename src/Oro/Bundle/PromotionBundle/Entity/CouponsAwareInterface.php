<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for entities for which coupons can be applied
 */
interface CouponsAwareInterface
{
    /**
     * @return Collection|Coupon[]
     */
    public function getCoupons();
}
