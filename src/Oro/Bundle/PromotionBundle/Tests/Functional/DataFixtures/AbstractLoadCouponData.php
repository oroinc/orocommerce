<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

abstract class AbstractLoadCouponData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPromotionData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getCoupons() as $key => $couponData) {
            $coupon = new Coupon();
            $coupon
                ->setCode($couponData['code'])
                ->setUsesPerCoupon($couponData['usesPerCoupon'])
                ->setUsesPerPerson($couponData['usesPerUser']);

            if (!empty($couponData['promotion'])) {
                /** @var Promotion $promotion */
                $promotion = $this->getReference($couponData['promotion']);
                $coupon->setPromotion($promotion);
            }

            if (!empty($couponData['validUntil'])) {
                $validUntil = new \DateTime($couponData['validUntil']);
                $coupon->setValidUntil($validUntil);
            }

            $manager->persist($coupon);
            $this->setReference($key, $coupon);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    abstract protected function getCoupons();
}
