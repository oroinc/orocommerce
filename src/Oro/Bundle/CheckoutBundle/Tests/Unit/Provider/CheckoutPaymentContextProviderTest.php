<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class CheckoutPaymentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentContextFactory;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var CheckoutPaymentContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentContextFactory = $this->createMock(CheckoutPaymentContextFactory::class);
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->provider = new CheckoutPaymentContextProvider(
            $this->paymentContextFactory,
            $this->memoryCacheProvider
        );
    }

    public function testGetContextWhenCache(): void
    {
        $this->paymentContextFactory->expects(self::never())
            ->method('create');

        $cachedContext = $this->createMock(PaymentContextInterface::class);

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
        $context = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextFactory->expects(self::once())
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
