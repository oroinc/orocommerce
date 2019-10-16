<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType as DBALWYSIWYGType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Searches attribute block type mapper by the specified tag
 * and add WYSIWYG block type to the list of the attributes block types.
 */
class AttributeBlockTypeMapperPass implements CompilerPassInterface
{
    private const WYSIWYG_FIELD_TYPE_KEY = DBALWYSIWYGType::TYPE;
    private const SERVICE_ID = 'oro_entity_config.layout.chain_attribute_block_type_mapper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::SERVICE_ID)) {
            $definition = $container->getDefinition(self::SERVICE_ID);
            $definition->addMethodCall('addBlockType', [
                self::WYSIWYG_FIELD_TYPE_KEY,
                'attribute_'. self::WYSIWYG_FIELD_TYPE_KEY
            ]);
        }
    }
}
