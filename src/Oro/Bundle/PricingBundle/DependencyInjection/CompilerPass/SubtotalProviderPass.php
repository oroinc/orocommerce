<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SubtotalProviderPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'orob2b_pricing.subtotal_processor.subtotal_provider_registry';
    const TAG = 'orob2b_pricing.subtotal_provider';
    const PRIORITY = 'priority';

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

        $providers      = [];
        foreach ($taggedServices as $serviceId => $tags) {
            $priority = isset($tags[0][self::PRIORITY]) ? $tags[0][self::PRIORITY] : 0;
            $providers[$priority][] = $serviceId;
        }

        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach ($providers as $provider) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($provider)]);
        }
    }
}
