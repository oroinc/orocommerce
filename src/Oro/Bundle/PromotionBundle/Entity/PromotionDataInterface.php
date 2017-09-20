<?php

namespace Oro\Bundle\PromotionBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\CronBundle\Entity\ScheduleIntervalsAwareInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\ScopeBundle\Entity\ScopeCollectionAwareInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

interface PromotionDataInterface extends
    RuleOwnerInterface,
    ScopeCollectionAwareInterface,
    ScheduleIntervalsAwareInterface,
    CouponsAwareInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return DiscountConfiguration
     */
    public function getDiscountConfiguration();

    /**
     * @return bool
     */
    public function isUseCoupons();

    /**
     * @return Collection|Selectable|Coupon[]
     */
    public function getCoupons();

    /**
     * @return Segment
     */
    public function getProductsSegment();
}
