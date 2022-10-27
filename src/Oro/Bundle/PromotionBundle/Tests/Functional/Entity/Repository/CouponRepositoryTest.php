<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CouponRepositoryTest extends WebTestCase
{
    protected function setUp(): void
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
            $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            -1
        ];
        $result = $this->getCouponRepository()->getCouponsWithPromotionByIds($ids);
        usort($result, static function (Coupon $a, Coupon $b) {
            return $a->getUsesPerCoupon() <=> $b->getUsesPerCoupon();
        });
        $this->assertEquals(
            [
                $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL),
                $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL),
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
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
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

    public function testGetCouponByCodeCaseSensitiveFound()
    {
        $coupon = $this->getCouponRepository()
            ->getSingleCouponByCode(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, false);
        $this->assertNotNull($coupon);
        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }

    public function testGetCouponByCodeCaseSensitiveNotFound()
    {
        $coupon = $this->getCouponRepository()
            ->getSingleCouponByCode(strtoupper(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL), false);
        $this->assertNull($coupon);
    }

    public function testGetCouponByCodeCaseInsensitiveFoundMoreThanOneInsensitiveResult()
    {
        if (!$this->isPostgreSql()) {
            $this->markTestSkipped('Applicable only for PostgreSQL');
        }

        $code = ucfirst(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->createCoupon($code);

        $coupon = $this->getCouponRepository()
            ->getSingleCouponByCode(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, true);
        $this->assertNull($coupon);
    }

    public function testGetCouponByCodeCaseSensitiveFoundMoreThanOneInsensitiveResult()
    {
        if (!$this->isPostgreSql()) {
            $this->markTestSkipped('Applicable only for PostgreSQL');
        }

        $code = ucfirst(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->createCoupon($code);

        $coupon = $this->getCouponRepository()
            ->getSingleCouponByCode(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, false);
        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }

    public function testGetCouponByCodeCaseInsensitiveFound()
    {
        $coupon = $this->getCouponRepository()
            ->getSingleCouponByCode(strtoupper(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL), true);
        $this->assertNotNull($coupon);
        $this->assertInstanceOf(Coupon::class, $coupon);
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }

    /**
     * @return ObjectRepository|CouponRepository
     */
    private function getCouponRepository()
    {
        return $this->getEntityManager()->getRepository(Coupon::class);
    }

    private function isPostgreSql(): bool
    {
        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Coupon::class);
    }

    private function createCoupon(string $code): Coupon
    {
        $manager = $this->getEntityManager();
        $coupon = new Coupon();
        $coupon
            ->setCode($code)
            ->setUsesPerCoupon(999)
            ->setUsesPerPerson(999)
            ->setEnabled(true);

        $manager->persist($coupon);
        $manager->flush($coupon);

        return $coupon;
    }
}
