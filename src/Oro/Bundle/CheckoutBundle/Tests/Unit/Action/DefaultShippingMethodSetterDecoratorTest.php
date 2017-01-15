<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterDecorator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultShippingMethodSetterDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DefaultShippingMethodSetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    /**
     * @var DefaultShippingMethodSetterDecorator
     */
    private $serviceDecorator;

    protected function setUp()
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

        $quoteDemand = $this
            ->getMockBuilder(QuoteDemand::class)
            ->getMock();

        $quote = $this
            ->getMockBuilder(Quote::class)
            ->getMock();

        $quoteDemand->expects(static::once())->method('getQuote')->willReturn($quote);
        $quote->expects(static::once())->method('getShippingMethod')->willReturn($shippingMethod);

        /** @var Checkout|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this
            ->getMockBuilder(Checkout::class)
            ->getMock();

        $checkout->expects(static::once())->method('getSourceEntity')->willReturn($quoteDemand);
        $checkout->expects(static::once())->method('setShippingMethod')->with($shippingMethod);

        $this->serviceDecorator->setDefaultShippingMethod($checkout);
    }
}
