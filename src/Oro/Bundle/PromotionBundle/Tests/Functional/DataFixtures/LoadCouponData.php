<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class LoadCouponData extends AbstractFixture implements DependentFixtureInterface
{
    const COUPON_WITHOUT_PROMO_AND_VALID_UNTIL = 'coupon_without_promo_and_valid_until';
    const COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL = 'coupon_with_promo_and_without_valid_until';
    const COUPON_WITH_PROMO_AND_EXPIRED = 'coupon_with_promo_and_expired';
    const COUPON_WITH_PROMO_AND_VALID_UNTIL = 'coupon_with_promo_and_valid_until';

    /**
     * @var array
     */
    protected static $coupons = [
        self::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL => [
            'code' => self::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL,
            'usesPerCoupon' => 1,
            'usesPerUser' => 1,
        ],
        self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL => [
            'code' => self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
            'usesPerCoupon' => 1,
            'usesPerUser' => 1,
            'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
        ],
        self::COUPON_WITH_PROMO_AND_EXPIRED => [
            'code' => self::COUPON_WITH_PROMO_AND_EXPIRED,
            'usesPerCoupon' => 1,
            'usesPerUser' => 1,
            'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
            'validUntil' => '-1 day',
        ],
        self::COUPON_WITH_PROMO_AND_VALID_UNTIL => [
            'code' => self::COUPON_WITH_PROMO_AND_VALID_UNTIL,
            'usesPerCoupon' => 3,
            'usesPerUser' => 2,
            'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
            'validUntil' => '+1 day',
        ],
    ];

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
        foreach (self::$coupons as $key => $couponData) {
            $coupon = new Coupon();
            $coupon
                ->setCode($couponData['code'])
                ->setUsesPerCoupon($couponData['usesPerCoupon'])
                ->setUsesPerUser($couponData['usesPerUser']);

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
}
