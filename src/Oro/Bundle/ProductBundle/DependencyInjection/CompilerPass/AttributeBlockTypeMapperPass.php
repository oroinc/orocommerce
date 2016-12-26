<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeBlockTypeMapperPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_product.attribute_block_type_mapper.product';
    const TAG              = 'oro_product.attribute_block_type_mapper';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (0 === count($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        $ids = array_keys($taggedServices);
        foreach ($ids as $id) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
