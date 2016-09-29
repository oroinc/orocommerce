<?php

namespace Oro\Bundle\ShippingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ShippingMethodsCompilerPass implements CompilerPassInterface
{
    const TAG = 'oro_shipping_method_provider';
    const SERVICE = 'oro_shipping.shipping_method.registry';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(static::TAG);
        if (!$taggedServices) {
            return;
        }

        $providers = new \SplPriorityQueue();

        $definition = $container->getDefinition(static::SERVICE);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = 0;
                if (array_key_exists('priority', $tag)) {
                    $priority = $tag['priority'];
                }
                $providers->insert(new Reference($id), $priority);
            }
        }

        foreach ($providers as $provider) {
            $definition->addMethodCall('addProvider', [$provider]);
        }
    }
}
