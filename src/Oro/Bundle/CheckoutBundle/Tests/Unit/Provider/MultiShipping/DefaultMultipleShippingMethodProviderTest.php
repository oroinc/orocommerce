<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class DefaultMultipleShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var DefaultMultipleShippingMethodProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->provider = new DefaultMultipleShippingMethodProvider($this->shippingMethodProvider);
    }

    public function testGetShippingMethod()
    {
        $multiShippingShippingMethod = $this->createMock(MultiShippingMethod::class);
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn(['multi_shipping_1' => $multiShippingShippingMethod]);

        $result = $this->provider->getShippingMethod();
        $this->assertEquals($multiShippingShippingMethod, $result);
    }

    public function testGetShippingMethodThrowsException()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There are no enabled multi shipping methods');

        $this->provider->getShippingMethod();
    }

    public function testGetShippingMethods()
    {
        $multiShippingShippingMethod1 = $this->createMock(MultiShippingMethod::class);
        $multiShippingShippingMethod2 = $this->createMock(MultiShippingMethod::class);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'multi_shipping_1' => $multiShippingShippingMethod1,
                'multi_shipping_2' => $multiShippingShippingMethod2,
            ]);

        $result = $this->provider->getShippingMethods();
        $this->assertEquals(['multi_shipping_1', 'multi_shipping_2'], $result);
    }

    public function testGetShippingMethodsThrowsException()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There are no enabled multi shipping methods');

        $this->provider->getShippingMethods();
    }

    public function testHasShippingMethodsReturnsTrue()
    {
        $multiShippingShippingMethod1 = $this->createMock(MultiShippingMethod::class);
        $multiShippingShippingMethod2 = $this->createMock(MultiShippingMethod::class);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'multi_shipping_1' => $multiShippingShippingMethod1,
                'multi_shipping_2' => $multiShippingShippingMethod2,
            ]);

        $this->assertTrue($this->provider->hasShippingMethods());
    }

    public function testHasShippingMethodsReturnsFalse()
    {
        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([]);

        $this->assertFalse($this->provider->hasShippingMethods());
    }
}
