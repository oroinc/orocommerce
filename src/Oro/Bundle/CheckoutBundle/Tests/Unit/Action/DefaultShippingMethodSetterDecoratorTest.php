<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterDecorator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class DefaultShippingMethodSetterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultShippingMethodSetter|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultShippingMethodSetter;

    /** @var DefaultShippingMethodSetterDecorator */
    private $defaultShippingMethodSetterDecorator;

    protected function setUp(): void
    {
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetter::class);

        $this->defaultShippingMethodSetterDecorator = new DefaultShippingMethodSetterDecorator(
            $this->defaultShippingMethodSetter
        );
    }

    private function getCheckout(object $sourceEntity = null): Checkout
    {
        $checkout = new Checkout();
        if (null !== $sourceEntity) {
            $source = $this->createMock(CheckoutSource::class);
            $source->expects(self::any())
                ->method('getEntity')
                ->willReturn($sourceEntity);
            $checkout->setSource($source);
        }

        return $checkout;
    }

    public function testSetDefaultShippingMethodNull()
    {
        $checkout = $this->getCheckout();

        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with(self::identicalTo($checkout));

        $this->defaultShippingMethodSetterDecorator->setDefaultShippingMethod($checkout);

        self::assertNull($checkout->getShippingMethod());
    }

    public function testSetDefaultShippingMethod()
    {
        $shippingMethod = 'flat_rate_1';
        $shippingMethodType = 'primarty';

        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteDemand->expects(self::exactly(2))
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $quoteDemand->expects(self::exactly(2))
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);

        $checkout = $this->getCheckout($quoteDemand);

        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->defaultShippingMethodSetterDecorator->setDefaultShippingMethod($checkout);

        self::assertEquals($shippingMethod, $checkout->getShippingMethod());
        self::assertEquals($shippingMethodType, $checkout->getShippingMethodType());
    }

    public function testSetDefaultShippingMethodWithoutSourceShippingMethod()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteDemand->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn(null);
        $quoteDemand->expects($this->never())
            ->method('getShippingMethodType');

        $checkout = $this->getCheckout($quoteDemand);

        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with(self::identicalTo($checkout));

        $this->defaultShippingMethodSetterDecorator->setDefaultShippingMethod($checkout);

        self::assertNull($checkout->getShippingMethod());
        self::assertNull($checkout->getShippingMethodType());
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

        $checkout = $this->getCheckout($quoteDemand);

        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with(self::identicalTo($checkout));

        $this->defaultShippingMethodSetterDecorator->setDefaultShippingMethod($checkout);

        self::assertNull($checkout->getShippingMethod());
        self::assertNull($checkout->getShippingMethodType());
    }
}
