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

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach ($providers as $id) {
            $registryDefinition->addMethodCall('addProvider', [$id]);
        }
    }
}
