<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AppliedCouponTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new AppliedCoupon(), [
            ['id', 42],
            ['appliedPromotion', new AppliedPromotion()],
            ['couponCode', 'some string'],
            ['sourcePromotionId', 42],
            ['sourceCouponId', 42],
            ['createdAt', new \DateTime()]
        ]);
    }
}
