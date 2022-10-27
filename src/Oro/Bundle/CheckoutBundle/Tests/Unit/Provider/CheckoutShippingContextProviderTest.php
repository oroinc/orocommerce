<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProviderTest extends \PHPUnit\Framework\TestCase
{
    use MemoryCacheProviderAwareTestTrait;

    /** @var CheckoutShippingContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingContextFactory;

    /** @var CheckoutShippingContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutShippingContextFactory = $this->createMock(CheckoutShippingContextFactory::class);

        $this->provider = new CheckoutShippingContextProvider($this->checkoutShippingContextFactory);
    }

    public function testGetContextWhenCache(): void
    {
        $this->checkoutShippingContextFactory
            ->expects($this->never())
            ->method('create');

        $context = $this->createMock(ShippingContextInterface::class);

        $this->mockMemoryCacheProvider($context);
        $this->setMemoryCacheProvider($this->provider);

        $this->assertEquals($context, $this->provider->getContext($this->createMock(Checkout::class)));
    }

    public function testGetContext(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $context = $this->createMock(ShippingContextInterface::class);

        $this->checkoutShippingContextFactory
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
