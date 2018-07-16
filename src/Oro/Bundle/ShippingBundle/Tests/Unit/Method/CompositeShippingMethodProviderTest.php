<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class CompositeShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompositeShippingMethodProvider
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->shippingMethodProvider = new CompositeShippingMethodProvider();

        $this->provider = $this->getMockBuilder(ShippingMethodProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetMethods()
    {
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        $this->assertInternalType('array', $shippingMethods);
        $this->assertEmpty($shippingMethods);
    }

    public function testRegistry()
    {
        $method = $this->createMock(ShippingMethodInterface::class);

        $this->provider->expects($this->once())
            ->method('getShippingMethods')
            ->willReturn(['test_name' => $method]);

        $this->provider->expects($this->once())
            ->method('getShippingMethod')
            ->with('test_name')
            ->willReturn($method);

        $this->provider->expects($this->once())
            ->method('hasShippingMethod')
            ->with('test_name')
            ->willReturn(true);

        $this->shippingMethodProvider->addProvider($this->provider);
        $this->assertEquals($method, $this->shippingMethodProvider->getShippingMethod('test_name'));
        $this->assertEquals(['test_name' => $method], $this->shippingMethodProvider->getShippingMethods());
    }

    public function testRegistryWrongMethod()
    {
        $this->assertNull($this->shippingMethodProvider->getShippingMethod('wrong_name'));
    }
}
