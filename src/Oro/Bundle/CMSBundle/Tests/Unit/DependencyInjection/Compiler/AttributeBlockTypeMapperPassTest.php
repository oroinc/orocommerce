<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AttributeBlockTypeMapperPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapperPass */
    private $compiler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->compiler = new AttributeBlockTypeMapperPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $mapperDef = $container->register('oro_entity_config.layout.chain_attribute_block_type_mapper');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                ['addBlockType', ['wysiwyg', 'attribute_wysiwyg']]
            ],
            $mapperDef->getMethodCalls()
        );
    }

    public function testProcessWithoutAttributeBlockTypeMapper()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }
}
