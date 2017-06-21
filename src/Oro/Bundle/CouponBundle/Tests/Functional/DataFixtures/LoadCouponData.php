<?php

namespace Oro\Bundle\CouponBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CouponBundle\Entity\Coupon;

class LoadCouponData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $coupon = new Coupon();
        $coupon
            ->setCode('test123')
            ->setUsesPerCoupon(1)
            ->setUsesPerUser(1);
        $manager->persist($coupon);

        $coupon2 = new Coupon();
        $coupon2
            ->setCode('test456')
            ->setUsesPerCoupon(2)
            ->setUsesPerUser(2);
        $manager->persist($coupon2);

        $manager->flush();
    }
}
