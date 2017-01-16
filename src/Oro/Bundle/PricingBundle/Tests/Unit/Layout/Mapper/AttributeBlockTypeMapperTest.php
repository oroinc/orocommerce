<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\PricingBundle\Layout\Mapper\AttributeBlockTypeMapper;

class AttributeBlockTypeMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeBlockTypeMapper */
    private $mapper;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->mapper = new AttributeBlockTypeMapper($this->registry);
    }

    public function testGetBlockTypeFromFieldNameRegistry()
    {
        $this->mapper->addBlockTypeByFieldName('SomeFieldName', 'attribute_type');

        $attribute = new FieldConfigModel();
        $attribute->setFieldName('SomeFieldName');

        $this->assertEquals('attribute_type', $this->mapper->getBlockType($attribute));
    }
}
