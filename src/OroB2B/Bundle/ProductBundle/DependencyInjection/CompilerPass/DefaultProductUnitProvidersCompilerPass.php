<?php

namespace OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DefaultProductUnitProvidersCompilerPass implements CompilerPassInterface
{
    const TAG = 'orob2b_product.default_product_unit_provider';
    const SERVICE = 'orob2b_product.provider.default_product_unit_provider.chain';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->getService())) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds($this->getTag());
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        krsort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $resolverDef = $container->getDefinition($this->getService());
        foreach ($providers as $provider) {
            $resolverDef->addMethodCall('addProvider', [$provider]);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return self::TAG;
    }

    /**
     * {@inheritdoc}
     */
    protected function getService()
    {
        return self::SERVICE;
    }
}
