<?php

namespace Oro\Bundle\CatalogBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\CatalogBundle\Entity\CategoryLongDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds support of the CategoryTitle, CategoryLongDescription and CategoryShortDescription entities
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
                [CategoryTitle::class, 'attribute_localized_fallback']
            );
            $definition->addMethodCall(
                'addBlockTypeUsingMetadata',
                [CategoryLongDescription::class, 'attribute_localized_fallback']
            );
            $definition->addMethodCall(
                'addBlockTypeUsingMetadata',
                [CategoryShortDescription::class, 'attribute_localized_fallback']
            );
        }
    }
}
