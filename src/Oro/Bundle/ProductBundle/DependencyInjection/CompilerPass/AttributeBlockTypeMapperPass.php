<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds support of the ProductName, ProductDescription and ProductShortDescription entities
 * by the attribute localized fallback block type.
 */
class AttributeBlockTypeMapperPass implements CompilerPassInterface
{
    private const SERVICE_ID = 'oro_entity_config.layout.chain_attribute_block_type_mapper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::SERVICE_ID)) {
            $definition = $container->getDefinition(self::SERVICE_ID);
            $definition->addMethodCall(
                'addBlockTypeUsingMetadata',
                [ProductName::class, 'attribute_localized_fallback']
            );
            $definition->addMethodCall(
                'addBlockTypeUsingMetadata',
                [ProductDescription::class, 'attribute_localized_fallback']
            );
            $definition->addMethodCall(
                'addBlockTypeUsingMetadata',
                [ProductShortDescription::class, 'attribute_localized_fallback']
            );
        }
    }
}
