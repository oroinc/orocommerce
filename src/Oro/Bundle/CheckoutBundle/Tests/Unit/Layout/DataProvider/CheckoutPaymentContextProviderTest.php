<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;

class CheckoutPaymentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutPaymentContextFactory| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentContextFactory;

    /**
     * @var Checkout| \PHPUnit\Framework\MockObject\MockObject
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
