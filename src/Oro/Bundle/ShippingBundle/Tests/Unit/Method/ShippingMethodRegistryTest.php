<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class ShippingMethodRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @var ShippingMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $method;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->registry = new ShippingMethodRegistry();

        $this->method = $this->getMockBuilder('Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->registry, $this->method);
    }

    public function testGetMethods()
    {
        $shippingMethods = $this->registry->getShippingMethods();
        $this->assertInternalType('array', $shippingMethods);
        $this->assertEmpty($shippingMethods);
    }

    public function testAddShippingMethod()
    {
        $this->registry->addShippingMethod($this->method);
        $this->assertContains($this->method, $this->registry->getShippingMethods());
    }

    public function testRegistry()
    {
        $this->method->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('test_name');

        $this->registry->addShippingMethod($this->method);
        $this->assertEquals($this->method, $this->registry->getShippingMethod('test_name'));
        $this->assertEquals(['test_name' => $this->method], $this->registry->getShippingMethods());
    }

    public function testRegistryWrongMethod()
    {
        $this->assertNull($this->registry->getShippingMethod('wrong_name'));
    }
}
