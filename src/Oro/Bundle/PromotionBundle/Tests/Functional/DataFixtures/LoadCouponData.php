<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

class LoadCouponData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $coupon = new Coupon();
        $coupon
            ->setCode('test123')
            ->setUsesPerCoupon(1)
            ->setUsesPerPerson(1);
        $manager->persist($coupon);

        $coupon2 = new Coupon();
        $coupon2
            ->setCode('test456')
            ->setUsesPerCoupon(2)
            ->setUsesPerPerson(2);

        $manager->persist($coupon2);

        $manager->flush();
    }
}
