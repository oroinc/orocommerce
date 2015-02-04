<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\AttributeType;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface;
use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;

class AttributeTypeRegistryTest extends \PHPUnit_Framework_TestCase
{

    const ATTRIBUTE_TYPE_NAME = 'test';

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
            ->will($this->returnValue(self::ATTRIBUTE_TYPE_NAME));

        /** @var \Symfony\Component\Translation\TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->registry = new AttributeTypeRegistry($translator);
        $this->registry->addType($this->attributeTypeMock);
    }

    public function testGetTypeByNameNull()
    {
        $this->assertNull($this->registry->getTypeByName('not_exists_one'));
    }

    public function testGetTypeByNameResult()
    {
        $this->assertEquals($this->attributeTypeMock, $this->registry->getTypeByName(self::ATTRIBUTE_TYPE_NAME));
    }

    public function testGetTypes()
    {
        $this->assertContains($this->attributeTypeMock, $this->registry->getTypes());
    }

    public function testGetChoices()
    {
        $this->assertArrayHasKey(self::ATTRIBUTE_TYPE_NAME, $this->registry->getChoices());
    }

}
