<?php

namespace OroB2B\Bundle\OrderBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SubtotalProviderPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'orob2b_order.subtotal_provider.registry';
    const TAG = 'orob2b_order.subtotal_provider';

    /**
     * {@inheritdoc}
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
            $registryDefinition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
