<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CouponValidationHandlerTest extends WebTestCase
{
    use RolePermissionExtension;

    private CouponValidationHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadCouponData::class,
            LoadOrderLineItemData::class
        ]);

        self::getContainer()->get('security.token_storage')->setToken($this->getToken());
        $this->updateRolePermission('ROLE_ADMINISTRATOR', Order::class, AccessLevel::GLOBAL_LEVEL, 'EDIT');

        self::getContainer()->get('request_stack')
            ->push(Request::create($this->getUrl('oro_promotion_validate_coupon_applicability')));

        $this->handler = self::getContainer()->get('oro_promotion.handler.coupon_validation_handler');
    }

    private function getToken(): UsernamePasswordOrganizationToken
    {
        /** @var User $user */
        $user = self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['email' => self::AUTH_USER]);

        return new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );
    }

    public function testHandleWhenNoCouponId(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The coupon ID is not specified in request parameters.');

        $request = new Request();
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity class is not specified in request parameters.');

        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenUnknownEntityClass(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Cannot resolve entity class "SomeBundle\SomeUnknownClass".');

        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => 'SomeBundle\SomeUnknownClass'
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => Promotion::class,
            'entityId' => $this->getReference(LoadPromotionData::SHIPPING_PROMOTION)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenEntityDoesNotHaveNeededPermissions(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->updateRolePermission('ROLE_ADMINISTRATOR', Order::class, AccessLevel::NONE_LEVEL, 'EDIT');

        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandleWhenNoEntityId(): void
    {
        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => Order::class
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
        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_1)->getId()
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

    public function testHandleWhenCouponDoesNotExistById(): void
    {
        $couponId = PHP_INT_MAX;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Cannot find "%s" entity with ID "%s".', Coupon::class, $couponId));

        $request = new Request([], [
            'couponId' => $couponId,
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_2)->getId()
        ]);
        $this->handler->handle($request);
    }

    public function testHandle(): void
    {
        $request = new Request([], [
            'couponId' => $this->getReference(LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL)->getId(),
            'entityClass' => Order::class,
            'entityId' => $this->getReference(LoadOrders::ORDER_2)->getId()
        ]);
        $response = $this->handler->handle($request);
        self::assertJsonResponseStatusCodeEquals($response, 200);
        self::assertSame(
            ['success' => true, 'errors' => []],
            self::jsonToArray($response->getContent())
        );
    }
}
