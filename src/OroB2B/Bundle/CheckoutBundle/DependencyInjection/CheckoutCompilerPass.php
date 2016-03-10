<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('orob2b_checkout.data_provider.manager')) {
            return;
        }
        $definition = $container->findDefinition('orob2b_checkout.data_provider.manager');
        $taggedServices = $container->findTaggedServiceIds('checkout.data_provider');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addProvider',
                [new Reference($id)]
            );
        }
    }
}
