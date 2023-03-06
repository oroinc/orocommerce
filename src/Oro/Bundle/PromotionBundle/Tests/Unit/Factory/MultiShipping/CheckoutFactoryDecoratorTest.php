<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Factory\MultiShipping\CheckoutFactoryDecorator;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Component\Testing\ReflectionUtil;

class CheckoutFactoryDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutFactory;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAware;

    /** @var CheckoutFactoryDecorator */
    private $factoryDecorator;

    protected function setUp(): void
    {
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->promotionAware = $this->getMockBuilder(PromotionAwareEntityHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isCouponAware'])
            ->getMock();

        $this->factoryDecorator = new CheckoutFactoryDecorator($this->checkoutFactory, $this->promotionAware);
    }

    public function testCreateCheckout()
    {
        $appliedCoupon = new AppliedCoupon();
        ReflectionUtil::setId($appliedCoupon, 1);
        $appliedCoupon->setCouponCode('TEST');
        $appliedCoupon->setSourceCouponId(2);
        $appliedCoupon->setSourcePromotionId(1);

        $sourceCheckout = new Checkout();
        $sourceCheckout->addAppliedCoupon($appliedCoupon);

        $resultCheckout = new Checkout();

        $this->checkoutFactory->expects(self::once())
            ->method('createCheckout')
            ->with($sourceCheckout, [])
            ->willReturn($resultCheckout);

        $this->promotionAware->expects($this->once())
            ->method('isCouponAware')
            ->willReturn(true);
        $checkout = $this->factoryDecorator->createCheckout($sourceCheckout, []);

        self::assertCount(1, $checkout->getAppliedCoupons());
    }
}
