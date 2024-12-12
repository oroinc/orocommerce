<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutWithAppliedCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FrontendAppliedCouponManagerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private FrontendAppliedCouponManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadCheckoutWithAppliedCouponData::class,
            LoadCombinedProductPrices::class,
            LoadOrderLineItemData::class
        ]);

        self::getContainer()->get('security.token_storage')->setToken($this->getToken());

        $this->manager = self::getContainer()->get('oro_promotion.frontend_applied_coupon_manager');
    }

    private function getToken(): UsernamePasswordOrganizationToken
    {
        $user = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        return new UsernamePasswordOrganizationToken(
            $user,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );
    }

    public function testGetAppliedCouponsWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $entity = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);
        $this->manager->getAppliedCoupons($entity);
    }

    public function testGetAppliedCouponsWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        $this->manager->getAppliedCoupons($entity);
    }

    public function testGetAppliedCoupons(): void
    {
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        $entity->addAppliedCoupon(new AppliedCoupon());
        $appliedCoupons = $this->manager->getAppliedCoupons($entity);

        self::assertCount(1, $appliedCoupons);
        self::assertEquals(
            $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1_COUPON_1)->getId(),
            $appliedCoupons[0]->getAppliedCoupon()->getId()
        );
        self::assertEquals(
            $this->getReference(LoadPromotionData::SHIPPING_PROMOTION)->getId(),
            $appliedCoupons[0]->getPromotion()->getId()
        );
    }

    public function testApplyCouponWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $entity = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->manager->applyCoupon($entity, $coupon->getCode());
    }

    public function testApplyCouponWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->manager->applyCoupon($entity, $coupon->getCode());
    }

    public function testApplyCouponWhenUnknownCouponCode(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->applyCoupon($entity, 'wrong-code', $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.invalid_coupon_code'],
            $errors->toArray()
        );
    }

    public function testApplyCouponWhenUnknownCouponCodeAndErrorCollectionIsNotProvided(): void
    {
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->applyCoupon($entity, 'wrong-code')
        );
    }

    public function testApplyCouponWhenItAlreadyAppliedToEntity(): void
    {
        $errors = new ArrayCollection();
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
        self::assertFalse(
            $this->manager->applyCoupon($entity, $coupon->getCode(), $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.coupon_already_added'],
            $errors->toArray()
        );
    }

    public function testApplyCouponWhenItAlreadyAppliedToEntityAndErrorCollectionIsNotProvided(): void
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
        self::assertFalse(
            $this->manager->applyCoupon($entity, $coupon->getCode())
        );
    }

    public function testApplyCouponWhenCouponIsNotApplicable(): void
    {
        $errors = new ArrayCollection();
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_EXPIRED);
        self::assertFalse(
            $this->manager->applyCoupon($entity, $coupon->getCode(), $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.expired'],
            $errors->toArray()
        );
    }

    public function testApplyCoupon(): void
    {
        $errors = new ArrayCollection();
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        self::assertTrue(
            $this->manager->applyCoupon($entity, $coupon->getCode(), $errors)
        );
        self::assertCount(0, $errors);
        self::assertCount(3, $entity->getAppliedCoupons());
        /** @var AppliedCoupon $appliedCoupon */
        $appliedCoupon = $entity->getAppliedCoupons()->last();
        self::assertEquals($coupon->getCode(), $appliedCoupon->getCouponCode());
        self::assertNotNull($appliedCoupon->getId());
    }

    public function testApplyCouponWhenFlushIsNotRequested(): void
    {
        $errors = new ArrayCollection();
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        self::assertTrue(
            $this->manager->applyCoupon($entity, $coupon->getCode(), $errors, false)
        );
        self::assertCount(0, $errors);
        self::assertCount(3, $entity->getAppliedCoupons());
        /** @var AppliedCoupon $appliedCoupon */
        $appliedCoupon = $entity->getAppliedCoupons()->last();
        self::assertEquals($coupon->getCode(), $appliedCoupon->getCouponCode());
        self::assertNull($appliedCoupon->getId());
    }

    public function testRemoveAppliedCouponWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $entity = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);
        $appliedCoupon = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1_COUPON_1);
        $this->manager->removeAppliedCoupon($entity, $appliedCoupon);
    }

    public function testRemoveAppliedCouponWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        $appliedCoupon = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1_COUPON_1);
        $this->manager->removeAppliedCoupon($entity, $appliedCoupon);
    }

    public function testRemoveAppliedCouponWhenItIsNotAppliedToEntity(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->removeAppliedCoupon($entity, new AppliedCoupon(), $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.remove_coupon.not_found'],
            $errors->toArray()
        );
    }

    public function testRemoveAppliedCouponWhenItIsNotAppliedToEntityAndErrorCollectionIsNotProvided(): void
    {
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->removeAppliedCoupon($entity, new AppliedCoupon())
        );
    }

    public function testRemoveAppliedCoupon(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var AppliedCoupon $appliedCouponToRemove */
        $appliedCouponToRemove = $entity->getAppliedCoupons()->first();
        $appliedCouponToRemoveId = $appliedCouponToRemove->getId();
        self::assertTrue(
            $this->manager->removeAppliedCoupon($entity, $appliedCouponToRemove, $errors)
        );
        self::assertCount(0, $errors);
        self::assertCount(1, $entity->getAppliedCoupons());
        self::assertNull(
            self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                ->find(AppliedCoupon::class, $appliedCouponToRemoveId)
        );
    }

    public function testRemoveAppliedCouponWhenFlushIsNotRequested(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var AppliedCoupon $appliedCouponToRemove */
        $appliedCouponToRemove = $entity->getAppliedCoupons()->first();
        $appliedCouponToRemoveId = $appliedCouponToRemove->getId();
        self::assertTrue(
            $this->manager->removeAppliedCoupon($entity, $appliedCouponToRemove, $errors, false)
        );
        self::assertCount(0, $errors);
        self::assertCount(1, $entity->getAppliedCoupons());
        self::assertNotNull(
            self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                ->find(AppliedCoupon::class, $appliedCouponToRemoveId)
        );
    }

    public function testRemoveAppliedCouponByCodeWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $entity = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->manager->removeAppliedCouponByCode($entity, $coupon->getCode());
    }

    public function testRemoveAppliedCouponByCodeWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL);
        $this->manager->removeAppliedCouponByCode($entity, $coupon->getCode());
    }

    public function testRemoveAppliedCouponByCodeWhenUnknownCouponCode(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->removeAppliedCouponByCode($entity, 'wrong-code', $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.remove_coupon.not_found'],
            $errors->toArray()
        );
    }

    public function testRemoveAppliedCouponByCodeWhenUnknownCouponCodeAndErrorCollectionIsNotProvided(): void
    {
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        self::assertFalse(
            $this->manager->removeAppliedCouponByCode($entity, 'wrong-code')
        );
    }

    public function testRemoveAppliedCouponByCode(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
        /** @var AppliedCoupon $appliedCouponToRemove */
        $appliedCouponToRemove = $entity->getAppliedCoupons()->first();
        $appliedCouponToRemoveId = $appliedCouponToRemove->getId();
        self::assertEquals($coupon->getCode(), $appliedCouponToRemove->getCouponCode());
        self::assertTrue(
            $this->manager->removeAppliedCouponByCode($entity, $coupon->getCode(), $errors)
        );
        self::assertCount(0, $errors);
        self::assertCount(1, $entity->getAppliedCoupons());
        self::assertNull(
            self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                ->find(AppliedCoupon::class, $appliedCouponToRemoveId)
        );
    }

    public function testRemoveAppliedCouponByCodeWhenFlushIsNotRequested(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
        /** @var AppliedCoupon $appliedCouponToRemove */
        $appliedCouponToRemove = $entity->getAppliedCoupons()->first();
        $appliedCouponToRemoveId = $appliedCouponToRemove->getId();
        self::assertEquals($coupon->getCode(), $appliedCouponToRemove->getCouponCode());
        self::assertTrue(
            $this->manager->removeAppliedCouponByCode($entity, $coupon->getCode(), $errors, false)
        );
        self::assertCount(0, $errors);
        self::assertCount(1, $entity->getAppliedCoupons());
        self::assertNotNull(
            self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                ->find(AppliedCoupon::class, $appliedCouponToRemoveId)
        );
    }

    public function testRemoveAppliedCouponByCodeCaseSensitive(): void
    {
        $errors = new ArrayCollection();
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var Coupon $coupon */
        $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
        self::assertFalse(
            $this->manager->removeAppliedCouponByCode($entity, strtoupper($coupon->getCode()), $errors)
        );
        self::assertEquals(
            ['oro.promotion.coupon.violation.remove_coupon.not_found'],
            $errors->toArray()
        );
    }

    public function testRemoveAppliedCouponByCodeCaseInsensitive(): void
    {
        $configManager = self::getConfigManager();
        self::assertFalse($configManager->get('oro_promotion.case_insensitive_coupon_search'));
        $configManager->set('oro_promotion.case_insensitive_coupon_search', true);
        $configManager->flush();
        try {
            $errors = new ArrayCollection();
            $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
            /** @var Coupon $coupon */
            $coupon = $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL);
            /** @var AppliedCoupon $appliedCouponToRemove */
            $appliedCouponToRemove = $entity->getAppliedCoupons()->first();
            $appliedCouponToRemoveId = $appliedCouponToRemove->getId();
            self::assertEquals($coupon->getCode(), $appliedCouponToRemove->getCouponCode());
            self::assertTrue(
                $this->manager->removeAppliedCouponByCode($entity, strtoupper($coupon->getCode()), $errors)
            );
            self::assertCount(0, $errors);
            self::assertCount(1, $entity->getAppliedCoupons());
            self::assertNull(
                self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                    ->find(AppliedCoupon::class, $appliedCouponToRemoveId)
            );
        } finally {
            $configManager->set('oro_promotion.case_insensitive_coupon_search', false);
            $configManager->flush();
        }
    }
}
