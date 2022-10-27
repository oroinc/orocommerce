<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AttributeBlockTypeMapperPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapperPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new AttributeBlockTypeMapperPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $mapperDef = $container->register('oro_entity_config.layout.chain_attribute_block_type_mapper');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                ['addBlockTypeUsingMetadata', [ProductName::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [ProductDescription::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [ProductShortDescription::class, 'attribute_localized_fallback']]
            ],
            $mapperDef->getMethodCalls()
        );
    }

    public function testProcessWithoutAttributeBlockTypeMapper(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }
}
