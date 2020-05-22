<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\CouponsTrait;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;

class EntityCouponsProviderTest extends \PHPUnit\Framework\TestCase
{
    use CouponsTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var EntityCouponsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new EntityCouponsProvider(
            $this->doctrineHelper
        );
    }

    public function testGetCouponsWithInvalidEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Given entity must implement either %s or %s',
            CouponsAwareInterface::class,
            AppliedCouponsAwareInterface::class
        ));

        $this->provider->getCoupons(new \stdClass());
    }

    public function testGetCouponsWithCouponsAwareEntity()
    {
        $coupons = [new Coupon(), new Coupon()];
        $order = $this->getEntity(AppliedPromotionData::class, ['coupons' => $coupons]);

        $this->assertEquals($coupons, $this->provider->getCoupons($order));
    }

    public function testGetCouponsWithAppliedCouponsAwareEntity()
    {
        $promotionId = 1;
        $couponId1 = 1;
        $couponCode1 = 'first-code';
        $couponId2 = 2;
        $couponCode2 = 'second-code';
        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => $promotionId]);

        $coupons = new ArrayCollection([
            $this->createCoupon($couponId1, $couponCode1, $promotion)->setUsesPerPerson(null)->setUsesPerCoupon(null),
            $this->createCoupon($couponId2, $couponCode2, $promotion)->setUsesPerPerson(null)->setUsesPerCoupon(null)
        ]);
        $appliedCoupons = [
            $this->createAppliedCoupon($couponId1, $couponCode1, $promotionId),
            $this->createAppliedCoupon($couponId2, $couponCode2, $promotionId),
        ];

        $this->doctrineHelper->expects($this->any())
            ->method('createEntityInstance')
            ->willReturnCallback(function ($class) {
                return new $class;
            });
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturnMap([
                [Promotion::class, true, 'id'],
                [Coupon::class, true, 'id']
            ]);

        $order = $this->getEntity(Order::class, ['appliedCoupons' => $appliedCoupons]);

        $this->assertEquals($coupons, $this->provider->getCoupons($order));
    }

    public function testCreateAppliedCouponByCoupon()
    {
        $promotionId = 1;
        $couponId1 = 1;
        $couponCode1 = 'first-code';
        /** @var Promotion $promotion */
        $promotion = $this->getEntity(Promotion::class, ['id' => $promotionId]);

        $coupon = $this->createCoupon($couponId1, $couponCode1, $promotion);
        $appliedCoupon = $this->createAppliedCoupon($couponId1, $couponCode1, $promotionId);

        $this->assertEquals($appliedCoupon, $this->provider->createAppliedCouponByCoupon($coupon));
    }
}
