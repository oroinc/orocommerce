<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

abstract class AbstractLoadCouponData extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadPromotionData::class,
            LoadOrganization::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->getCoupons() as $key => $couponData) {
            $coupon = new Coupon();
            $coupon
                ->setCode($couponData['code'])
                ->setUsesPerCoupon($couponData['usesPerCoupon'])
                ->setUsesPerPerson($couponData['usesPerPerson']);

            if (isset($couponData['enabled'])) {
                $coupon->setEnabled($couponData['enabled']);
            } else {
                $coupon->setEnabled(true);
            }

            if (!empty($couponData['promotion'])) {
                /** @var Promotion $promotion */
                $promotion = $this->getReference($couponData['promotion']);
                $coupon->setPromotion($promotion);
            }

            if (!empty($couponData['validFrom'])) {
                $validFrom = new \DateTime($couponData['validFrom']);
                $coupon->setValidFrom($validFrom);
            }

            if (!empty($couponData['validUntil'])) {
                $validUntil = new \DateTime($couponData['validUntil']);
                $coupon->setValidUntil($validUntil);
            }

            $coupon->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));

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
