<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponData::class
        ]);
    }

    public function testGetCouponsWithPromotionByIds()
    {
        $ids = [
            $this->getReference(LoadCouponData::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL)->getId(),
            $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL)->getId(),
            $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            -1
        ];
        $result = $this->getCouponRepository()->getCouponsWithPromotionByIds($ids);
        usort($result, function (Coupon $a, Coupon $b) {
            return $a->getUsesPerCoupon() >= $b->getUsesPerCoupon();
        });
        $this->assertEquals(
            [
                $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL),
                $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL),
            ],
            $result
        );
    }

    public function testGetPromotionsWithMatchedCoupons()
    {
        $promotionsIds = [
            $this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId(),
            $this->getReference(LoadPromotionData::ORDER_AMOUNT_PROMOTION)->getId(),
        ];
        $couponCodes = [
            LoadCouponData::COUPON_WITH_PROMO_AND_EXPIRED,
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL,
            LoadCouponData::COUPON_DISABLED,
        ];

        $result = $this->getCouponRepository()->getPromotionsWithMatchedCoupons($promotionsIds, $couponCodes);

        $this->assertEquals([$this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId()], $result);
    }

    public function testGetPromotionsWithMatchedCouponsWithNumericCouponCode()
    {
        $promotionsIds = [$this->getReference(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId()];

        $this->assertEmpty($this->getCouponRepository()->getPromotionsWithMatchedCoupons($promotionsIds, [1234567]));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository|CouponRepository
     */
    private function getCouponRepository()
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);
    }
}
