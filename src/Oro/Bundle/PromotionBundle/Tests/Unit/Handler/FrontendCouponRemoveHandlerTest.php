<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponRemoveHandler;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FrontendCouponRemoveHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationChecker;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var FrontendCouponRemoveHandler
     */
    private $handler;

    private PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject $promotionAwareHelper;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);
        $this->handler = new FrontendCouponRemoveHandler(
            $this->authorizationChecker,
            $this->registry,
            $this->promotionAwareHelper
        );
    }

    public function testHandleRemoveWhenOrderIsPassed()
    {
        $entity = new Order();
        $appliedCoupon = new AppliedCoupon();

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->expectException(AccessDeniedException::class);

        $this->handler->handleRemove($entity, $appliedCoupon);
    }

    public function testHandleRemoveWhenAccessToEntityNotGranted()
    {
        $entity = new Checkout();
        $appliedCoupon = new AppliedCoupon();

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $entity)
            ->willReturn(false);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->expectException(AccessDeniedException::class);

        $this->handler->handleRemove($entity, $appliedCoupon);
    }

    public function testHandleRemoveWhenCouponDoesNotBelongToEntity()
    {
        $entity = new Checkout();
        /** @var AppliedCoupon $coupon1 */
        $coupon1 = $this->getEntity(AppliedCoupon::class, ['id' => 1]);
        /** @var AppliedCoupon $coupon2 */
        $coupon2 = $this->getEntity(AppliedCoupon::class, ['id' => 2]);
        $entity->addAppliedCoupon($coupon2);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $entity)
            ->willReturn(true);
        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->expectException(NotFoundHttpException::class);

        $this->handler->handleRemove($entity, $coupon1);
    }

    public function testHandle()
    {
        $entity = new Checkout();
        /** @var AppliedCoupon $coupon1 */
        $coupon1 = $this->getEntity(AppliedCoupon::class, ['id' => 1]);
        /** @var AppliedCoupon $coupon2 */
        $coupon2 = $this->getEntity(AppliedCoupon::class, ['id' => 2]);
        $entity->addAppliedCoupon($coupon1);
        $entity->addAppliedCoupon($coupon2);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', $entity)
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($coupon1);
        $em->expects($this->once())
            ->method('flush');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(AppliedCoupon::class)
            ->willReturn($em);

        $this->assertCount(2, $entity->getAppliedCoupons());
        $this->handler->handleRemove($entity, $coupon1);
        $this->assertCount(1, $entity->getAppliedCoupons());
        $this->assertFalse($entity->getAppliedCoupons()->contains($coupon1));
        $this->assertTrue($entity->getAppliedCoupons()->contains($coupon2));
    }
}
