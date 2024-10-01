<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\CouponsTrait;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;

class EntityCouponsProviderTest extends \PHPUnit\Framework\TestCase
{
    use CouponsTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var EntityCouponsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $this->provider = new EntityCouponsProvider($this->doctrineHelper, $this->promotionAwareHelper);
    }

    public function testGetCouponsWithInvalidEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Given entity must have is_coupon_aware entity config or ' .
            'implement the Oro\Bundle\PromotionBundle\Entity\CouponsAwareInterface interface',
        );

        $this->provider->getCoupons(new \stdClass());
    }

    public function testGetCouponsWithCouponsAwareEntity()
    {
        $coupon1 = new Coupon();
        $coupon2 = new Coupon();
        $order = new AppliedPromotionData();
        $order->addCoupon($coupon1);
        $order->addCoupon($coupon2);

        $coupons = $this->provider->getCoupons($order);
        $this->assertCount(2, $coupons);
        $this->assertSame($coupon1, $coupons[0]);
        $this->assertSame($coupon2, $coupons[1]);
    }

    public function testGetCouponsWithAppliedCouponsAwareEntity()
    {
        $promotionId = 1;
        $couponId1 = 1;
        $couponCode1 = 'first-code';
        $couponId2 = 2;
        $couponCode2 = 'second-code';
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $promotionId);

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
                return new $class();
            });
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturnMap([
                [Promotion::class, true, 'id'],
                [Coupon::class, true, 'id']
            ]);

        $order = new Order();
        $order->setAppliedCoupons(new ArrayCollection($appliedCoupons));

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEquals($coupons, $this->provider->getCoupons($order));
    }

    public function testCreateAppliedCouponByCoupon()
    {
        $promotionId = 1;
        $couponId1 = 1;
        $couponCode1 = 'first-code';
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $promotionId);

        $coupon = $this->createCoupon($couponId1, $couponCode1, $promotion);
        $appliedCoupon = $this->createAppliedCoupon($couponId1, $couponCode1, $promotionId);

        $this->assertEquals($appliedCoupon, $this->provider->createAppliedCouponByCoupon($coupon));
    }
}
