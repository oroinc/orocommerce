<?php
namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Interface for coupon providers
 */
interface EntityCouponsProviderInterface
{
    /**
     * @param object $entity
     * @return Collection|Selectable|Coupon[]
     */
    public function getCoupons($entity);
}
