<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CouponTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testPropertyAccessors()
    {
        $now = new \DateTime('now');
        $this->assertPropertyAccessors(
            new Coupon(),
            [
                ['code', 'some string'],
                ['totalUses', 1],
                ['usesPerCoupon', 1],
                ['usesPerUser', 1],
                ['owner', new BusinessUnit()],
                ['organization', new Organization()],
                ['createdAt', $now, false],
                ['updatedAt', $now, false],
                ['validUntil', $now],
            ]
        );
    }
}
