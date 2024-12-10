<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Handler;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponRemoveHandler;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCheckoutWithAppliedCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadPromotionData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @dbIsolationPerTest
 */
class FrontendCouponRemoveHandlerTest extends WebTestCase
{
    use RolePermissionExtension;

    private FrontendCouponRemoveHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadCheckoutWithAppliedCouponData::class,
            LoadOrderLineItemData::class
        ]);

        self::getContainer()->get('security.token_storage')->setToken($this->getToken());
        $this->updateRolePermission('ROLE_FRONTEND_ADMINISTRATOR', Order::class, AccessLevel::GLOBAL_LEVEL, 'EDIT');

        $this->handler = self::getContainer()->get('oro_promotion.handler.frontend_coupon_remove_handler');
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

    public function testHandleWhenEntityDoesNotHaveNeededPermissions(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->updateRolePermission('ROLE_FRONTEND_ADMINISTRATOR', Order::class, AccessLevel::NONE_LEVEL, 'EDIT');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        $this->handler->handleRemove($entity, new AppliedCoupon());
    }

    public function testHandleWhenEntityIsNotCouponAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be coupon aware.');

        $entity = $this->getReference(LoadPromotionData::SHIPPING_PROMOTION);
        $this->handler->handleRemove($entity, new AppliedCoupon());
    }

    public function testHandleWhenEntityIsPromotionAware(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The entity must be not promotion aware.');

        $entity = $this->getReference(LoadOrders::ORDER_1);
        $this->handler->handleRemove($entity, new AppliedCoupon());
    }

    public function testHandleWhenUnknownCouponCode(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        $this->handler->handleRemove($entity, new AppliedCoupon());
    }

    public function testHandle(): void
    {
        $entity = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1);
        /** @var AppliedCoupon $appliedCoupon */
        $appliedCoupon = $this->getReference(LoadCheckoutWithAppliedCouponData::PROMOTION_CHECKOUT_1_COUPON_1);
        $appliedCouponId = $appliedCoupon->getId();
        $this->handler->handleRemove($entity, $appliedCoupon);

        self::assertCount(1, $entity->getAppliedCoupons());
        self::assertNull(
            self::getContainer()->get('doctrine')->getManagerForClass(AppliedCoupon::class)
                ->find(AppliedCoupon::class, $appliedCouponId)
        );
    }
}
