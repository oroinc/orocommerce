<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Factory\MultiShipping\CheckoutFactoryDecorator;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CheckoutFactoryDecoratorTest extends TestCase
{
    use EntityTrait;

    private CheckoutFactoryInterface $checkoutFactory;
    private CheckoutFactoryDecorator $factoryDecorator;

    protected function setUp(): void
    {
        $this->checkoutFactory = $this->createMock(CheckoutFactoryInterface::class);
        $this->factoryDecorator = new CheckoutFactoryDecorator($this->checkoutFactory);
    }

    public function testCreateCheckout()
    {
        $appliedCoupon = $this->getEntity(AppliedCoupon::class, [
            'id' => 1,
            'couponCode' => 'TEST',
            'sourcePromotionId' => 1,
            'sourceCouponId' => 2,
        ]);

        $sourceCheckout = new Checkout();
        $sourceCheckout->addAppliedCoupon($appliedCoupon);

        $resultCheckout = new Checkout();

        $this->checkoutFactory->expects($this->once())
            ->method('createCheckout')
            ->with($sourceCheckout, [])
            ->willReturn($resultCheckout);

        $checkout = $this->factoryDecorator->createCheckout($sourceCheckout, []);

        $this->assertCount(1, $checkout->getAppliedCoupons());
    }
}
