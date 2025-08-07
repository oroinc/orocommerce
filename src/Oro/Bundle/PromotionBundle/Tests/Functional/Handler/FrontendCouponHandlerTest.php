<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponHandler;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendCouponHandlerTest extends WebTestCase
{
    use RolePermissionExtension;
    use ConfigManagerAwareTestTrait;

    private FrontendCouponHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadCouponData::class,
            LoadCheckoutData::class,
            LoadCombinedProductPrices::class,
            LoadOrderLineItemData::class
        ]);

        self::getContainer()->get('security.token_storage')->setToken($this->getToken());
        $this->updateRolePermission('ROLE_FRONTEND_ADMINISTRATOR', Order::class, AccessLevel::GLOBAL_LEVEL, 'EDIT');

        self::getContainer()->get('request_stack')
            ->push(Request::create($this->getUrl('oro_promotion_frontend_add_coupon')));

        $this->handler = self::getContainer()->get('oro_promotion.handler.frontend_coupon_handler');
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

    public function testHandleWhenNoCouponCode(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The coupon code is not specified in request parameters.');

        $request = new Request();
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity class is not specified in request parameters.');

        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenUnknownEntityClass(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Cannot resolve entity class "SomeBundle\SomeUnknownClass".');

        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => 'SomeBundle\SomeUnknownClass'
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => Promotion::class,
            'entityId' => $this->getReference(LoadPromotionData::SHIPPING_PROMOTION)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityDoesNotHaveNeededPermissions(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->updateRolePermission('ROLE_FRONTEND_ADMINISTRATOR', Order::class, AccessLevel::NONE_LEVEL, 'EDIT');

        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityId(): void
    {
        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => Checkout::class
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            [
                'success' => false,
                'errors' => ['oro.promotion.coupon.violation.coupon_promotion_not_applicable']
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testHandleWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenUnknownCouponCode(): void
    {
        $request = new Request([], [
            'couponCode' => 'wrong-code',
            'entityClass' => Checkout::class,
            'entityId' => $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1)->getId()
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            [
                'success' => false,
                'errors' => ['oro.promotion.coupon.violation.invalid_coupon_code']
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testHandle(): void
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode(),
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId()
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            ['success' => true, 'errors' => []],
            self::jsonToArray($response->getContent())
        );

        $expectedAppliedCoupons = array_values($entity->getAppliedCoupons()->toArray());
        self::assertCount(1, $expectedAppliedCoupons);
        self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class)->refresh($entity);

        $appliedCoupons = $entity->getAppliedCoupons()->toArray();
        self::assertEquals($expectedAppliedCoupons, $appliedCoupons);
    }

    public function testHandleCaseSensitive(): void
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $code = strtoupper($this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode());
        $postData = [
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
            'couponCode' => $code
        ];

        $request = new Request([], $postData);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            [
                'success' => false,
                'errors' => ['oro.promotion.coupon.violation.invalid_coupon_code']
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testHandleCaseInsensitive(): void
    {
        $configManager = self::getConfigManager(null);
        self::assertFalse($configManager->get('oro_promotion.case_insensitive_coupon_search'));
        $configManager->set('oro_promotion.case_insensitive_coupon_search', true);
        $configManager->flush();
        try {
            /** @var Checkout $entity */
            $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
            $code = strtoupper(
                $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getCode()
            );
            $postData = [
                'entityClass' => Checkout::class,
                'entityId' => $entity->getId(),
                'couponCode' => $code
            ];

            $request = new Request([], $postData);
            $response = $this->handler->handle($request);

            self::assertJsonResponseStatusCodeEquals($response, 200);
            self::assertSame(
                ['success' => true, 'errors' => []],
                self::jsonToArray($response->getContent())
            );

            $expectedAppliedCoupons = array_values($entity->getAppliedCoupons()->toArray());
            self::assertCount(1, $expectedAppliedCoupons);
            self::getContainer()->get('doctrine')->getManagerForClass(Checkout::class)->refresh($entity);

            $appliedCoupons = $entity->getAppliedCoupons()->toArray();
            self::assertEquals($expectedAppliedCoupons, $appliedCoupons);
        } finally {
            $configManager->set('oro_promotion.case_insensitive_coupon_search', false);
            $configManager->flush();
            $configManager->reload();
        }
    }

    public function testHandleShippingPromotion(): void
    {
        /** @var Checkout $entity */
        $entity = $this->getReference(LoadCheckoutData::PROMOTION_CHECKOUT_1);
        $request = new Request([], [
            'couponCode' => $this->getReference(LoadCouponData::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL)->getCode(),
            'entityClass' => Checkout::class,
            'entityId' => $entity->getId(),
        ]);
        $response = $this->handler->handle($request);

        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            ['success' => true, 'errors' => []],
            self::jsonToArray($response->getContent())
        );
    }
}
