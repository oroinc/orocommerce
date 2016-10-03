<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingMethodRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->registry = new ShippingMethodRegistry();

        $this->provider = $this->getMockBuilder(ShippingMethodProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetMethods()
    {
        $shippingMethods = $this->registry->getShippingMethods();
        $this->assertInternalType('array', $shippingMethods);
        $this->assertEmpty($shippingMethods);
    }

    public function testRegistry()
    {
        $method = $this->getMock(ShippingMethodInterface::class);

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

        $this->registry->addProvider($this->provider);
        $this->assertEquals($method, $this->registry->getShippingMethod('test_name'));
        $this->assertEquals(['test_name' => $method], $this->registry->getShippingMethods());
    }

    public function testRegistryWrongMethod()
    {
        $this->assertNull($this->registry->getShippingMethod('wrong_name'));
    }
}
