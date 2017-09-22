<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponUsage;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AppliedCouponEntityListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponData::class,
            LoadOrders::class
        ]);
    }

    public function testPostPersist()
    {
        $this->assertEmpty($this->findAllCouponUsage());

        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);

        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($coupon->getCode());
        $appliedCoupon->setSourceCouponId($coupon->getId());
        $appliedCoupon->setSourcePromotionId($coupon->getPromotion()->getId());
        $appliedCoupon->setOrder($order);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();

        $couponUsage = $this->findAllCouponUsage();

        $expectedCouponUsage = new CouponUsage();
        $expectedCouponUsage->setCoupon($coupon)
            ->setPromotion($coupon->getPromotion())
            ->setCustomerUser($order->getCustomerUser());

        $this->assertCount(1, $couponUsage);
        $actualCouponUsage = reset($couponUsage);

        $this->assertSame($coupon, $actualCouponUsage->getCoupon());
        $this->assertSame($coupon->getPromotion(), $actualCouponUsage->getPromotion());
        $this->assertSame($order->getCustomerUser(), $actualCouponUsage->getCustomerUser());
    }

    public function testPostPersistWithoutCoupon()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('not_existing_coupon_code');
        $appliedCoupon->setSourceCouponId(123);
        $appliedCoupon->setSourcePromotionId(42);
        $appliedCoupon->setOrder($order);

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();

        $this->assertEmpty($this->findAllCouponUsage());
    }

    public function testPostPersistWithoutOrderCoupon()
    {
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_UNTIL);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($coupon->getCode());
        $appliedCoupon->setSourceCouponId($coupon->getId());
        $appliedCoupon->setSourcePromotionId($coupon->getPromotion()->getId());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'AppliedCoupon object should have order for Oro\Bundle\PromotionBundle\Entity\CouponUsage creation.'
        );

        $em = $this->getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        $em->flush();
    }

    /**
     * @return array|CouponUsage[]
     */
    private function findAllCouponUsage()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(CouponUsage::class)
            ->getRepository(CouponUsage::class)
            ->findAll();
    }
}
