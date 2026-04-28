<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponUsageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CouponUsageManagerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadCouponUsageData::class
        ]);
    }

    public function testGetCouponUsageCount()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEquals(3, $manager->getCouponUsageCount($coupon));
    }

    public function testGetCouponUsageCountByCustomerUser()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEquals(2, $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));
    }

    public function testCreateCouponUsage()
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $customerUser = $this->getReference(LoadCustomerUserData::ANONYMOUS_EMAIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $this->assertEmpty($manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));

        $manager->createCouponUsage($coupon, $customerUser, true);
        $manager->createCouponUsage($coupon, $customerUser, true);
        $manager->createCouponUsage($coupon, $customerUser, false);
        $this->assertEquals(2, $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));
    }

    public function testRevertCouponUsages(): void
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $count = $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser);
        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId($coupon->getId());
        $manager->revertCouponUsages(new ArrayCollection([$appliedCoupon]), $customerUser);

        $this->assertEquals(--$count, $manager->getCouponUsageCountByCustomerUser($coupon, $customerUser));
    }

    public function testRevertCouponUsagesWithNullCustomerUser(): void
    {
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);

        $manager = $this->getContainer()->get('oro_promotion.coupon_usage_manager');

        $count = $manager->getCouponUsageCount($coupon);
        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId($coupon->getId());
        $manager->revertCouponUsages(new ArrayCollection([$appliedCoupon]), null);

        $this->assertEquals(--$count, $manager->getCouponUsageCount($coupon));
    }
}
