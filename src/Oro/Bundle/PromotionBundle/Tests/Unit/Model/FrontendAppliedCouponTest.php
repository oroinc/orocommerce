<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Model;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\FrontendAppliedCoupon;

class FrontendAppliedCouponTest extends \PHPUnit\Framework\TestCase
{
    public function testModel(): void
    {
        $appliedCoupon = $this->createMock(AppliedCoupon::class);
        $promotion = $this->createMock(Promotion::class);
        $frontendAppliedCoupon = new FrontendAppliedCoupon($appliedCoupon, $promotion);
        self::assertSame($appliedCoupon, $frontendAppliedCoupon->getAppliedCoupon());
        self::assertSame($promotion, $frontendAppliedCoupon->getPromotion());
    }
}
