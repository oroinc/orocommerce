<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AttributeBlockTypeMapperPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapperPass */
    private $compilerPass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    protected function setUp(): void
    {
        $this->compilerPass = new AttributeBlockTypeMapperPass();
        $this->container = $this->createMock(ContainerBuilder::class);
    }

    public function testProcess(): void
    {
        $definition = new Definition();

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn($definition);

        $this->compilerPass->process($this->container);

        $this->assertEquals(
            [
                ['addBlockTypeUsingMetadata', [ProductName::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [ProductDescription::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [ProductShortDescription::class, 'attribute_localized_fallback']],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testProcessWithoutAttributeBlockTypeMapper(): void
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_entity_config.layout.chain_attribute_block_type_mapper')
            ->willReturn(false);

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }
}
