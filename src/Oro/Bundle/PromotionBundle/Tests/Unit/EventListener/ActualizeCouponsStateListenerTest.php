<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
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
                $this->getCoupon($couponId1, $couponCode1, $promotion),
                $this->getCoupon($couponId2, $couponCode2, $promotion),
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
        $entity = $this->createMock(AppliedCouponsAwareInterface::class);
        $entity->expects($this->exactly(2))
            ->method('addAppliedCoupon')
            ->withConsecutive(
                [$this->getAppliedCoupon($couponId1, $couponCode1, $promotionId)],
                [$this->getAppliedCoupon($couponId2, $couponCode2, $promotionId)]
            );
        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->listener->onBeforeTotalCalculate($event);
    }

    /**
     * @param integer $id
     * @param string $code
     * @param Promotion $promotion
     * @return Coupon|object
     */
    private function getCoupon(int $id, string $code, Promotion $promotion)
    {
        return $this->getEntity(
            Coupon::class,
            ['id' => $id, 'code' => $code, 'promotion' => $promotion]
        );
    }

    /**
     * @param integer $id
     * @param string $code
     * @param integer $promotionId
     * @return AppliedCoupon
     */
    private function getAppliedCoupon(int $id, string $code, int $promotionId)
    {
        $appliedCoupon = new AppliedCoupon();

        return $appliedCoupon
            ->setSourceCouponId($id)
            ->setCouponCode($code)
            ->setSourcePromotionId($promotionId);
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
