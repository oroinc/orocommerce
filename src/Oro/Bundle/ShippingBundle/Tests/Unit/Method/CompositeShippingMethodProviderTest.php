<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class CompositeShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider1;

    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider2;

    /** @var CompositeShippingMethodProvider */
    private $shippingMethodProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(ShippingMethodProviderInterface::class);
        $this->provider2 = $this->createMock(ShippingMethodProviderInterface::class);

        $this->shippingMethodProvider = new CompositeShippingMethodProvider(
            [$this->provider1, $this->provider2]
        );
    }

    public function testGetShippingMethods()
    {
        $method1 = $this->createMock(ShippingMethodInterface::class);
        $method2 = $this->createMock(ShippingMethodInterface::class);
        $method3 = $this->createMock(ShippingMethodInterface::class);
        $method4 = $this->createMock(ShippingMethodInterface::class);

        $this->provider1->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'method1' => $method1,
                'method2' => $method2
            ]);
        $this->provider2->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn([
                'method2' => $method3,
                'method4' => $method4
            ]);

        $this->assertSame(
            [
                'method1' => $method1,
                'method2' => $method3,
                'method4' => $method4
            ],
            $this->shippingMethodProvider->getShippingMethods()
        );
    }

    public function testGetShippingMethodWhenOneOfProvidersReturnRequestedShippingMethod()
    {
        $method1 = $this->createMock(ShippingMethodInterface::class);

        $this->provider1->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(true);
        $this->provider1->expects($this->once())
            ->method('getShippingMethod')
            ->with('method1')
            ->willReturn($method1);
        $this->provider2->expects($this->never())
            ->method('hasShippingMethod');
        $this->provider2->expects($this->never())
            ->method('getShippingMethod');

        $this->assertSame($method1, $this->shippingMethodProvider->getShippingMethod('method1'));
    }

    public function testGetShippingMethodWhenAllProvidersDoNotReturnRequestedShippingMethod()
    {
        $this->provider1->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(false);
        $this->provider1->expects($this->never())
            ->method('getShippingMethod');
        $this->provider2->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(false);
        $this->provider2->expects($this->never())
            ->method('getShippingMethod');

        $this->assertNull($this->shippingMethodProvider->getShippingMethod('method1'));
    }

    public function testHasShippingMethodWhenOneOfProvidersReturnTrue()
    {
        $this->provider1->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(true);
        $this->provider2->expects($this->never())
            ->method('hasShippingMethod');

        $this->assertTrue($this->shippingMethodProvider->hasShippingMethod('method1'));
    }

    public function testHasShippingMethodWhenAllProvidersReturnFalse()
    {
        $this->provider1->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(false);
        $this->provider2->expects($this->once())
            ->method('hasShippingMethod')
            ->with('method1')
            ->willReturn(false);

        $this->assertFalse($this->shippingMethodProvider->hasShippingMethod('method1'));
    }
}
