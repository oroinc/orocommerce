<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;

class CheckoutShippingContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutShippingContextFactory| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingContextFactory;

    /**
     * @var Checkout| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkout;

    /**
     * @var CheckoutShippingContextProvider
     */
    protected $provider;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    public function setUp()
    {
        $this->checkout = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextFactory = $this->getMockBuilder(CheckoutShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->provider = new CheckoutShippingContextProvider($this->shippingContextFactory, $this->cacheProvider);
    }

    public function testGetPaymentStatus()
    {
        $context = new ShippingContext([]);

        $cacheKey = CheckoutShippingContextProvider::class . \md5(\serialize($this->checkout));

        $this->shippingContextFactory->expects($this->once())
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

        $shippingContext = $this->provider->getContext($this->checkout);
        $this->assertEquals($context, $shippingContext);
    }

    public function testGetPaymentStatusCached()
    {
        $context = new ShippingContext([]);

        $cacheKey = CheckoutShippingContextProvider::class . \md5(\serialize($this->checkout));

        $this->shippingContextFactory->expects($this->once())
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

        $shippingContext = $this->provider->getContext($this->checkout);
        $this->assertSame($context, $shippingContext);

        $shippingContext = $this->provider->getContext($this->checkout);
        $this->assertSame($context, $shippingContext);
    }
}
