<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Model;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CouponApplicabilityQueryBuilderModifierTest extends WebTestCase
{
    /**
     * @var CouponApplicabilityQueryBuilderModifier
     */
    private $modifier;

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->loadFixtures([LoadCouponData::class]);
        $this->modifier = self::getContainer()->get('oro_promotion.model.coupon_applicability_query_builder_modifier');
    }

    public function testModify()
    {
        /** @var CouponRepository $repository */
        $repository = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);

        $queryBuilder = $repository->createQueryBuilder('c')->orderBy('c.usesPerCoupon');
        $coupons = $queryBuilder->getQuery()->getResult();
        $this->assertCount(7, $coupons);

        $this->modifier->modify($queryBuilder);
        $coupons = $queryBuilder->getQuery()->getResult();
        $codes = array_map(function (Coupon $coupon) {
            return $coupon->getCode();
        }, $coupons);
        sort($codes);
        $expected = [
            LoadCouponData::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL,
            LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL
        ];
        sort($expected);
        $this->assertCount(4, $coupons);
        $this->assertEquals($expected, $codes);
    }
}
