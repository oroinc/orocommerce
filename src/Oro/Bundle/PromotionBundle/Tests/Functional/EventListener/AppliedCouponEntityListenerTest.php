<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AppliedCouponEntityListenerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponData::class,
            LoadOrders::class
        ]);
    }

    public function testPostPersist(): void
    {
        self::assertEmpty($this->findAllCouponUsage());

        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($coupon->getCode());
        $appliedCoupon->setSourceCouponId($coupon->getId());
        $appliedCoupon->setSourcePromotionId($coupon->getPromotion()->getId());
        $appliedCoupon->setOrder($order);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();

        $couponUsage = $this->findAllCouponUsage();

        $expectedCouponUsage = new CouponUsage();
        $expectedCouponUsage->setCoupon($coupon)
            ->setPromotion($coupon->getPromotion())
            ->setCustomerUser($order->getCustomerUser());

        self::assertCount(1, $couponUsage);
        $actualCouponUsage = reset($couponUsage);

        self::assertSame($coupon, $actualCouponUsage->getCoupon());
        self::assertSame($coupon->getPromotion(), $actualCouponUsage->getPromotion());
        self::assertSame($order->getCustomerUser(), $actualCouponUsage->getCustomerUser());
    }

    public function testPostPersistWithoutCoupon(): void
    {
        $manager = $this->getCouponUsageManager();
        foreach ($this->findAllCouponUsage() as $entity) {
            $manager->remove($entity);
        }
        $manager->flush();

        self::assertEmpty($this->findAllCouponUsage());

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('not_existing_coupon_code');
        $appliedCoupon->setSourceCouponId(0);
        $appliedCoupon->setSourcePromotionId(42);
        $appliedCoupon->setOrder($order);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();

        self::assertEmpty($this->findAllCouponUsage());
    }

    public function testPostPersistHasDraftSessionUuid(): void
    {
        $manager = $this->getCouponUsageManager();
        foreach ($this->findAllCouponUsage() as $entity) {
            $manager->remove($entity);
        }
        $manager->flush();

        self::assertEmpty($this->findAllCouponUsage());

        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        $order->setDraftSessionUuid(UUIDGenerator::v4());

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($coupon->getCode());
        $appliedCoupon->setSourceCouponId($coupon->getId());
        $appliedCoupon->setSourcePromotionId($coupon->getPromotion()->getId());
        $appliedCoupon->setOrder($order);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();

        // Coupon usage should NOT be created for draft orders
        self::assertEmpty($this->findAllCouponUsage());
    }

    /**
     * @return array|CouponUsage[]
     */
    private function findAllCouponUsage(): array
    {
        return $this->getCouponUsageManager()
            ->getRepository(CouponUsage::class)
            ->findAll();
    }

    private function getCouponUsageManager(): ObjectManager
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(CouponUsage::class);
    }
}
