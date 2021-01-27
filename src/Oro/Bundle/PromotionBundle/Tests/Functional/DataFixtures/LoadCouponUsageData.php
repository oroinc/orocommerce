<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class LoadCouponUsageData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCouponData::class,
            LoadCustomerUserData::class,
            LoadPromotionData::class
        ];
    }

    /**
     * @var array
     */
    private $couponUsages = [
        [
            'coupon' => LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
            'customerUser' => LoadCustomerUserData::EMAIL
        ],
        [
            'coupon' => LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
            'customerUser' => LoadCustomerUserData::EMAIL
        ],
        [
            'coupon' => LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->couponUsages as $couponUsageData) {
            $couponUsage = new CouponUsage();

            /** @var Coupon $coupon */
            $coupon = $this->getReference($couponUsageData['coupon']);
            $couponUsage->setCoupon($coupon);

            /** @var Promotion $promotion */
            $promotion = $this->getReference($couponUsageData['promotion']);
            $couponUsage->setPromotion($promotion);

            if (array_key_exists('customerUser', $couponUsageData)) {
                /** @var CustomerUser $customerUser */
                $customerUser = $this->getReference($couponUsageData['customerUser']);
                $couponUsage->setCustomerUser($customerUser);
            }

            $manager->persist($couponUsage);
        }

        $manager->flush();
    }
}
