<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterDecorator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultShippingMethodSetterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DefaultShippingMethodSetter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $service;

    /**
     * @var DefaultShippingMethodSetterDecorator
     */
    private $serviceDecorator;

    protected function setUp(): void
    {
        $this->service = $this->getMockBuilder(DefaultShippingMethodSetter::class)
            ->disableOriginalConstructor()
            ->setMethods(['setDefaultShippingMethod'])->getMock();
        $this->serviceDecorator = new DefaultShippingMethodSetterDecorator($this->service);
    }

    public function testSetDefaultShippingMethodNull()
    {
        /** @var Checkout $checkout */
        $checkout = $this->getEntity(Checkout::class);

        $this->serviceDecorator->setDefaultShippingMethod($checkout);

        static::assertNull($checkout->getShippingMethod());
    }

    public function testSetDefaultShippingMethod()
    {
        $shippingMethod = 'flat_rate_1';
        $shippingMethodType = 'primarty';

        $quoteDemand = $this->createMock(QuoteDemand::class);

        $quoteDemand->expects(static::exactly(2))->method('getShippingMethod')->willReturn($shippingMethod);
        $quoteDemand->expects(static::exactly(2))->method('getShippingMethodType')->willReturn($shippingMethodType);

        /** @var Checkout|\PHPUnit\Framework\MockObject\MockObject $checkout */
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects(static::once())->method('getSourceEntity')->willReturn($quoteDemand);
        $checkout->expects(static::once())->method('setShippingMethod')->with($shippingMethod);
        $checkout->expects(static::once())->method('setShippingMethodType')->with($shippingMethodType);

        $this->serviceDecorator->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodWithoutSourceShippingMethod()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);

        $quoteDemand->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn(null);
        $quoteDemand->expects($this->never())
            ->method('getShippingMethodType');

        /* @var $checkout Checkout|\PHPUnit\Framework\MockObject\MockObject */
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemand);

        $checkout->expects($this->never())
            ->method('setShippingMethod');
        $checkout->expects($this->never())
            ->method('setShippingMethodType');

        $this->serviceDecorator->setDefaultShippingMethod($checkout);
    }

    public function testSetDefaultShippingMethodWithoutSourceShippingMethodType()
    {
        $shippingMethod = 'flat_rate_1';

        $quoteDemand = $this->createMock(QuoteDemand::class);

        $quoteDemand->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $quoteDemand->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn(null);

        /* @var $checkout Checkout|\PHPUnit\Framework\MockObject\MockObject */
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemand);

        $checkout->expects($this->never())
            ->method('setShippingMethod');
        $checkout->expects($this->never())
            ->method('setShippingMethodType');

        $this->serviceDecorator->setDefaultShippingMethod($checkout);
    }
}
