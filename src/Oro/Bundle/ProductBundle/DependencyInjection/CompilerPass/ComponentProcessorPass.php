<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ComponentProcessorPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_product.component_processor.registry';
    const TAG = 'oro_product.quick_add_processor';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach (array_keys($taggedServices) as $id) {
            $registryDefinition->addMethodCall(
                'addProcessor',
                [new Reference($id)]
            );
        }
    }
}
