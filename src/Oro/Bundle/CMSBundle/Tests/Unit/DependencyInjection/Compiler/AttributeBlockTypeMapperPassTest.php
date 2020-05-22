<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AttributeBlockTypeMapperPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapperPass */
    private $pass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->pass = new AttributeBlockTypeMapperPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess()
    {
        $attributeBlockTypeDefinition = new Definition();

        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn($attributeBlockTypeDefinition);

        $this->pass->process($this->container);

        $this->assertEquals([
            ['addBlockType', ['wysiwyg', 'attribute_wysiwyg']]
        ], $attributeBlockTypeDefinition->getMethodCalls());
    }

    public function testProcessWithoutAttributeBlockTypeMapper()
    {
        $this->container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn(false);

        $this->container
            ->expects($this->never())
            ->method('getDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper');

        $this->pass->process($this->container);
    }
}
