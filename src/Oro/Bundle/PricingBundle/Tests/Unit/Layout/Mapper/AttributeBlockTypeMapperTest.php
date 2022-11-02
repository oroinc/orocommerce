<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\PricingBundle\Layout\Mapper\AttributeBlockTypeMapper;

class AttributeBlockTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapper */
    private $mapper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
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
