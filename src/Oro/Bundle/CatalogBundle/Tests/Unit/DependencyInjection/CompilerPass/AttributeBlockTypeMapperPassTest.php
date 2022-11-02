<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;
use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
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
                ['addBlockTypeUsingMetadata', [CategoryTitle::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [CategoryLongDescription::class, 'attribute_localized_fallback']],
                ['addBlockTypeUsingMetadata', [CategoryShortDescription::class, 'attribute_localized_fallback']]
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
