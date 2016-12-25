<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;

class CheckoutPaymentContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutPaymentContextFactory| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentContextFactory;

    /**
     * @var Checkout| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkout;

    /**
     * @var CheckoutPaymentContextProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkout = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextFactory = $this->getMockBuilder(CheckoutPaymentContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new CheckoutPaymentContextProvider($this->paymentContextFactory);
    }

    public function testGetPaymentStatus()
    {
        $context = new PaymentContext([]);

        $this->paymentContextFactory->expects($this->once())
            ->method('create')
            ->with($this->checkout)
            ->willReturn($context);

        $paymentContext = $this->provider->getContext($this->checkout);
        $this->assertEquals($context, $paymentContext);
    }
}
