<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutShippingContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingContextFactory;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var CheckoutShippingContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingContextFactory = $this->createMock(CheckoutShippingContextFactory::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->provider = new CheckoutShippingContextProvider(
            $this->shippingContextFactory,
            $this->memoryCacheProvider
        );
    }

    public function testGetContextWhenCache(): void
    {
        $this->shippingContextFactory->expects(self::never())
            ->method('create');

        $cachedContext = $this->createMock(ShippingContextInterface::class);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function () use ($cachedContext) {
                return $cachedContext;
            });

        self::assertSame($cachedContext, $this->provider->getContext($this->createMock(Checkout::class)));
    }

    public function testGetContext(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $context = $this->createMock(ShippingContextInterface::class);

        $this->shippingContextFactory->expects(self::once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        self::assertSame($context, $this->provider->getContext($checkout));
    }
}
