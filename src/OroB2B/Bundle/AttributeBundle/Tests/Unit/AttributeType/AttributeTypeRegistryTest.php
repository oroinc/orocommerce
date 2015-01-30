<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\AttributeType;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;

class AttributeTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeTypeRegistry
     */
    private $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeInterface
     */
    private $attributeTypeMock;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->attributeTypeMock = $this->getMock('OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface');
        $this->attributeTypeMock
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test'));

        $this->registry = new AttributeTypeRegistry();
        $this->registry->addType($this->attributeTypeMock);
    }

    public function testGetTypeByNameNull()
    {
        $this->assertNull($this->registry->getTypeByName('not_exists_one'));
    }

    public function testGetTypeByNameResult()
    {
        $this->assertEquals($this->attributeTypeMock, $this->registry->getTypeByName('test'));
    }

    public function testGetTypes()
    {
        $this->assertContains($this->attributeTypeMock, $this->registry->getTypes());
    }
}
