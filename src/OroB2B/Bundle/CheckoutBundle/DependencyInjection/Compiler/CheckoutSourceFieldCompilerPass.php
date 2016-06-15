<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutSourceFieldCompilerPass implements CompilerPassInterface
{
    const GRID_CHECKOUT_LISTENER = 'orob2b_checkout.datagrid.checkout_source_field_listener';
    const GRID_CHECKOUT_SOURCE_TAG = 'checkout.source_field_definer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::GRID_CHECKOUT_LISTENER)) {
            return;
        }

        $definition = $container->getDefinition(self::GRID_CHECKOUT_LISTENER);
        $taggedServices = $container->findTaggedServiceIds(self::GRID_CHECKOUT_SOURCE_TAG);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addSourceDefinitionResolver',
                [new Reference($id)]
            );
        }
    }
}
