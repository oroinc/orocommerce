<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
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
        /** @var CouponRepository $repository */
        $repository = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);

        $ids = [
            $this->getReference(LoadCouponData::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL)->getId(),
            $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL)->getId(),
            $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL)->getId(),
            -1
        ];
        $result = $repository->getCouponsWithPromotionByIds($ids);
        usort($result, function(Coupon $a, Coupon $b) {
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
}
