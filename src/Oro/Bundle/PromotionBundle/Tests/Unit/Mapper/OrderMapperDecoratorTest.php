<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Mapper;

use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Mapper\OrderMapperDecorator;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;

class OrderMapperDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $orderMapper;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var OrderMapperDecorator */
    private $orderMapperDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderMapper = $this->createMock(MapperInterface::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $this->orderMapperDecorator = new OrderMapperDecorator($this->orderMapper, $this->promotionAwareHelper);
    }

    public function testMapWhenSourceEntityIsShoppingList()
    {
        $couponCode = 'First';
        $sourcePromotionId = 3;
        $sourceCouponId = 22;

        $appliedCoupon = new AppliedCoupon();
        ReflectionUtil::setId($appliedCoupon, 5);
        $appliedCoupon->setCouponCode($couponCode);
        $appliedCoupon->setSourcePromotionId($sourcePromotionId);
        $appliedCoupon->setSourceCouponId($sourceCouponId);

        $checkout = new Checkout();
        $checkout->addAppliedCoupon($appliedCoupon);

        $order = new Order();
        $data = ['paymentTerm' => 'Term30'];

        $this->orderMapper->expects($this->once())
            ->method('map')
            ->with($checkout, $data, ['appliedCoupons' => true])
            ->willReturn($order);

        $expectedAppliedCoupon = (new AppliedCoupon())
            ->setCouponCode($couponCode)
            ->setSourcePromotionId($sourcePromotionId)
            ->setSourceCouponId($sourceCouponId);

        $expectedOrder = new Order();
        $expectedOrder->addAppliedCoupon($expectedAppliedCoupon);

        $this->promotionAwareHelper->expects($this->any())->method('isCouponAware')->willReturn(true);
        static::assertEquals($expectedOrder, $this->orderMapperDecorator->map($checkout, $data));
    }
}
