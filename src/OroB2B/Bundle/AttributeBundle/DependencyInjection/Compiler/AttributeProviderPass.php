<?php

namespace OroB2B\Bundle\AttributeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeProviderPass implements CompilerPassInterface
{
    const ATTRIBUTE_TYPE_TAG = 'orob2b_attribute.attribute_type';
    const ATTRIBUTE_TYPE_REGISTRY = 'orob2b_attribute.attribute_type.registry';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::ATTRIBUTE_TYPE_REGISTRY)) {
            return;
        }

        $registry = $container->getDefinition(self::ATTRIBUTE_TYPE_REGISTRY);
        $attributeTypeTaggedServices = $container->findTaggedServiceIds(self::ATTRIBUTE_TYPE_TAG);

        foreach ($attributeTypeTaggedServices as $id => $tags) {
            $container->getDefinition($id)->setPublic(false);
            $registry->addMethodCall('addType', [new Reference($id)]);
        }
    }
}
