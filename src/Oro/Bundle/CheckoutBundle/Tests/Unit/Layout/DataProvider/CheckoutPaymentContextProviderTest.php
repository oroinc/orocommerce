<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
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

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    public function setUp()
    {
        $this->checkout = new Checkout();

        $this->paymentContextFactory = $this->getMockBuilder(CheckoutPaymentContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->provider = new CheckoutPaymentContextProvider($this->paymentContextFactory, $this->cacheProvider);
    }

    public function testGetPaymentStatus()
    {
        $context = new PaymentContext([]);

        $cacheKey = CheckoutPaymentContextProvider::class . \md5(\serialize($this->checkout));

        $this->paymentContextFactory->expects($this->once())
            ->method('create')
            ->with($this->checkout)
            ->willReturn($context);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn(false);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($cacheKey, $context);

        $paymentContext = $this->provider->getContext($this->checkout);
        $this->assertEquals($context, $paymentContext);
    }

    public function testGetPaymentStatusCached()
    {
        $context = new PaymentContext([]);

        $cacheKey = CheckoutPaymentContextProvider::class . \md5(\serialize($this->checkout));

        $this->paymentContextFactory->expects($this->once())
            ->method('create')
            ->with($this->checkout)
            ->willReturn($context);

        $this->cacheProvider->expects($this->exactly(2))
            ->method('fetch')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(false, $context);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($cacheKey, $context);

        $paymentContext = $this->provider->getContext($this->checkout);
        $this->assertSame($context, $paymentContext);

        $paymentContext = $this->provider->getContext($this->checkout);
        $this->assertSame($context, $paymentContext);
    }
}
