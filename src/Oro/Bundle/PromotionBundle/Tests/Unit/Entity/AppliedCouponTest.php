<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class AppliedCouponTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(new AppliedCoupon(), [
            ['id', 42],
            ['appliedPromotion', new AppliedPromotion()],
            ['couponCode', 'some string'],
            ['sourcePromotionId', 42],
            ['sourceCouponId', 42],
            ['createdAt', new \DateTime()],
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
        ]);
    }
}
