<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CouponRepositoryTest extends WebTestCase
{
    private CouponRepository $repository;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadCouponData::class]);
        $this->repository = $this->getEntityManager()->getRepository(Coupon::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Coupon::class);
    }

    private function createCoupon(string $code): void
    {
        $coupon = new Coupon();
        $coupon->setCode($code);
        $coupon->setUsesPerCoupon(999);
        $coupon->setUsesPerPerson(999);
        $coupon->setEnabled(true);

        $em = $this->getEntityManager();
        $em->persist($coupon);
        $em->flush($coupon);
    }

    private function getCoupon(string $reference): Coupon
    {
        return $this->getReference($reference);
    }

    private function getCouponId(string $reference): int
    {
        return $this->getCoupon($reference)->getId();
    }

    private function getPromotion(string $reference): Promotion
    {
        return $this->getReference($reference);
    }

    public function testGetCouponsWithPromotionByIds(): void
    {
        $ids = [
            $this->getCoupon(LoadCouponData::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL)->getId(),
            $this->getCoupon(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL)->getId(),
            $this->getCoupon(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            -1
        ];
        $result = $this->repository->getCouponsWithPromotionByIds($ids);
        usort($result, static function (Coupon $a, Coupon $b) {
            return $a->getUsesPerCoupon() <=> $b->getUsesPerCoupon();
        });
        $this->assertEquals(
            [
                $this->getCoupon(LoadCouponData::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL),
                $this->getCoupon(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL),
            ],
            $result
        );
    }

    public function testGetPromotionsWithMatchedCouponsIds(): void
    {
        $promotionsIds = [
            $this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId(),
            $this->getPromotion(LoadPromotionData::ORDER_AMOUNT_PROMOTION)->getId(),
        ];
        $couponCodes = [
            $this->getCouponId(LoadCouponData::COUPON_WITH_PROMO_AND_EXPIRED),
            $this->getCouponId(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL),
            $this->getCouponId(LoadCouponData::COUPON_DISABLED),
        ];

        $result = $this->repository->getPromotionsWithMatchedCouponsIds($promotionsIds, $couponCodes);

        $this->assertEquals([$this->getPromotion(LoadPromotionData::ORDER_PERCENT_PROMOTION)->getId()], $result);
    }

    public function testGetSingleCouponByCodeCaseSensitiveFound(): void
    {
        $coupon = $this->repository->getSingleCouponByCode(
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL
        );
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }

    public function testGetSingleCouponByCodeCaseSensitiveNotFound(): void
    {
        $coupon = $this->repository->getSingleCouponByCode(
            strtoupper(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)
        );
        $this->assertNull($coupon);
    }

    public function testGetSingleCouponByCodeCaseInsensitiveNotFound(): void
    {
        $coupon = $this->repository->getSingleCouponByCode('other', true);
        $this->assertNull($coupon);
    }

    public function testGetSingleCouponByCodeCaseInsensitiveFoundMoreThanOneInsensitiveResult(): void
    {
        $this->createCoupon(ucfirst(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL));

        $coupon = $this->repository->getSingleCouponByCode(
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
            true
        );
        $this->assertNull($coupon);
    }

    public function testGetSingleCouponByCodeCaseSensitiveFoundMoreThanOneInsensitiveResult(): void
    {
        $this->createCoupon(ucfirst(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL));

        $coupon = $this->repository->getSingleCouponByCode(
            LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL
        );
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }

    public function testGetSingleCouponByCodeCaseInsensitiveFound(): void
    {
        $coupon = $this->repository->getSingleCouponByCode(
            ucfirst(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL),
            true
        );
        $this->assertEquals(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL, $coupon->getCode());
    }
}
