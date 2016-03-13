<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutCompilerPass implements CompilerPassInterface
{
    const CHECKOUT_DATA_PROVIDER_MANAGER = 'orob2b_checkout.data_provider.manager';
    const CHECKOUT_DATA_PROVIDER_TAG = 'checkout.data_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHECKOUT_DATA_PROVIDER_MANAGER)) {
            return;
        }
        $definition = $container->findDefinition(self::CHECKOUT_DATA_PROVIDER_MANAGER);
        $taggedServices = $container->findTaggedServiceIds(self::CHECKOUT_DATA_PROVIDER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addProvider',
                [new Reference($id)]
            );
        }
    }
}
