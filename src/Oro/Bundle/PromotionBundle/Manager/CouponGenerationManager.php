<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\PromotionBundle\Service\CouponGeneratorInterface;
use Oro\Bundle\PromotionBundle\Service\CouponInserterInterface;

class CouponGenerationManager
{
    /**
     * @var CouponGeneratorInterface
     */
    protected $couponGenerator;

    /**
     * @var CouponInserterInterface
     */
    protected $couponInserter;

    public function __construct(CouponGeneratorInterface $couponGenerator, CouponInserterInterface $couponInserter)
    {
        $this->couponGenerator = $couponGenerator;
        $this->couponInserter = $couponInserter;
    }
}
