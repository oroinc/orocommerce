<?php

namespace Oro\Bundle\CouponBundle\Tests\Unit\Entity;

use Oro\Bundle\CouponBundle\Entity\Coupon;
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
                ['createdAt', $now, false],
                ['updatedAt', $now, false],
            ]
        );
    }
}
