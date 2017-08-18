<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\EventListener\ActualizeCouponsStateListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;

class ActualizeCouponsStateListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var ActualizeCouponsStateListener
     */
    private $listener;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->listener = new ActualizeCouponsStateListener($this->registry);
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
        $entity = $this->createMock(AppliedCouponsAwareInterface::class);
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
        $entity = $this->createMock(AppliedCouponsAwareInterface::class);
        $entity->expects($this->never())
            ->method('addAppliedCoupon');
        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculate()
    {
        $couponIds = '1,2';
        $request = $this->getRequest(['addedCouponIds' => $couponIds]);
        $coupon1 = $this->getEntity(Coupon::class, ['id' => 1]);
        $coupon2 = $this->getEntity(Coupon::class, ['id' => 2]);
        $repository = $this->createMock(CouponRepository::class);
        $repository->expects($this->once())
            ->method('getCouponsWithPromotionByIds')
            ->with(explode(',', $couponIds))
            ->willReturn([$coupon1, $coupon2]);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Coupon::class)
            ->willReturn($objectManager);
        $entity = $this->createMock(AppliedCouponsAwareInterface::class);
        $entity->expects($this->exactly(2))
            ->method('addAppliedCoupon')
            ->withConsecutive([$coupon1], [$coupon2]);
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
