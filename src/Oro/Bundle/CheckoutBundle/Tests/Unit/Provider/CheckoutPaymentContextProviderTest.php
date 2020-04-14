<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class CheckoutPaymentContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use MemoryCacheProviderAwareTestTrait;

    /** @var CheckoutPaymentContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutPaymentContextFactory;

    /** @var CheckoutPaymentContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutPaymentContextFactory = $this->createMock(CheckoutPaymentContextFactory::class);

        $this->provider = new CheckoutPaymentContextProvider($this->checkoutPaymentContextFactory);
    }

    public function testGetContextWhenCache(): void
    {
        $this->checkoutPaymentContextFactory
            ->expects($this->never())
            ->method('create');

        $context = $this->createMock(PaymentContextInterface::class);

        $this->mockMemoryCacheProvider($context);
        $this->setMemoryCacheProvider($this->provider);

        $this->assertEquals($context, $this->provider->getContext($this->createMock(Checkout::class)));
    }

    public function testGetContext(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $context = $this->createMock(PaymentContextInterface::class);

        $this->checkoutPaymentContextFactory
            ->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($context);

        $this->assertEquals($context, $this->provider->getContext($checkout));
    }

    public function testGetContextWhenMemoryCacheProvider(): void
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->provider);

        $this->testGetContext();
    }
}
