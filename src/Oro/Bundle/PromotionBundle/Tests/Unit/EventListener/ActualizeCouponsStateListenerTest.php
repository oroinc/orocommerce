<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\EventListener\ActualizeCouponsStateListener;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\CouponsTrait;
use Oro\Bundle\PromotionBundle\Tests\Unit\Stub\AppliedCouponsAwareStub;
use Symfony\Component\HttpFoundation\Request;

class ActualizeCouponsStateListenerTest extends \PHPUnit\Framework\TestCase
{
    use CouponsTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var EntityCouponsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityCouponsProvider;

    private PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject $promotionAwareHelper;

    /**
     * @var ActualizeCouponsStateListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityCouponsProvider = $this->createMock(EntityCouponsProvider::class);
        $this->promotionAwareHelper = $this->getMockBuilder(PromotionAwareEntityHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isCouponAware'])
            ->getMock();
        $this->listener = new ActualizeCouponsStateListener(
            $this->registry,
            $this->entityCouponsProvider,
            $this->promotionAwareHelper
        );
    }

    public function testOnBeforeTotalCalculateWhenEntityNotOrder()
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $event = new TotalCalculateBeforeEvent(new \stdClass(), $this->getRequest());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenRequestDoesNotHaveCouponsKey()
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $event = new TotalCalculateBeforeEvent($entity, $this->getRequest());
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenNoCouponsIds()
    {
        $request = $this->getRequest(['addedCouponIds' => '']);
        $repository = $this->createMock(CouponRepository::class);
        $repository->expects($this->never())
            ->method('getCouponsWithPromotionByIds');
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Coupon::class)
            ->willReturn($objectManager);
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects($this->never())
            ->method('addAppliedCoupon');
        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculate()
    {
        $couponIds = '1,2';
        $request = $this->getRequest(['addedCouponIds' => $couponIds]);

        $promotionId = 777;
        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => $promotionId]);

        $couponId1 = 1;
        $couponCode1 = 'first-code';
        $couponId2 = 2;
        $couponCode2 = 'second-code';

        $repository = $this->createMock(CouponRepository::class);
        $repository->expects($this->once())
            ->method('getCouponsWithPromotionByIds')
            ->with(explode(',', $couponIds))
            ->willReturn([
                $this->createCoupon($couponId1, $couponCode1, $promotion),
                $this->createCoupon($couponId2, $couponCode2, $promotion),
            ]);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Coupon::class)
            ->willReturn($objectManager);

        $this->entityCouponsProvider->expects($this->any())
            ->method('createAppliedCouponByCoupon')
            ->withConsecutive(
                [$this->createCoupon($couponId1, $couponCode1, $promotion)],
                [$this->createCoupon($couponId2, $couponCode2, $promotion)]
            )
            ->willReturnOnConsecutiveCalls(
                $this->createAppliedCoupon($couponId1, $couponCode1, $promotionId),
                $this->createAppliedCoupon($couponId2, $couponCode2, $promotionId)
            );

        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects($this->exactly(2))
            ->method('addAppliedCoupon')
            ->withConsecutive(
                [$this->createAppliedCoupon($couponId1, $couponCode1, $promotionId)],
                [$this->createAppliedCoupon($couponId2, $couponCode2, $promotionId)]
            );
        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    /**
     * @param array $postData
     * @return Request
     */
    private function getRequest(array $postData = [])
    {
        return new Request([], $postData);
    }
}
