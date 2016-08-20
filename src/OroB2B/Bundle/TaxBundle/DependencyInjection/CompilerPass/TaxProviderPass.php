<?php

namespace Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TaxProviderPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'orob2b_tax.provider.tax_provider_registry';
    const TAG = 'orob2b_tax.tax_provider';

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

        $providers = [];
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = $id;
        }

        krsort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        foreach ($providers as $provider) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($provider)]);
        }
    }
}
