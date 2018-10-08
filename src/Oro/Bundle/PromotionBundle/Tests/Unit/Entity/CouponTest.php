<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CouponTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testPropertyAccessors()
    {
        $now = new \DateTime('now');
        static::assertPropertyAccessors(
            new Coupon(),
            [
                ['code', 'some string'],
                ['enabled', true],
                ['usesPerCoupon', 1],
                ['usesPerPerson', 1],
                ['owner', new BusinessUnit()],
                ['organization', new Organization()],
                ['createdAt', $now, false],
                ['updatedAt', $now, false],
                ['validUntil', $now],
            ]
        );
    }
}
