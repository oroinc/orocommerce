<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutCompilerPass implements CompilerPassInterface
{
    const CHECKOUT_DATA_PROVIDER_MANAGER = 'orob2b_checkout.data_provider.manager.checkout_line_items';
    const CHECKOUT_DATA_PROVIDER_TAG = 'checkout.data_provider.line_item';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHECKOUT_DATA_PROVIDER_MANAGER)) {
            return;
        }
        $definition = $container->getDefinition(self::CHECKOUT_DATA_PROVIDER_MANAGER);
        $taggedServices = $container->findTaggedServiceIds(self::CHECKOUT_DATA_PROVIDER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addProvider',
                [new Reference($id)]
            );
        }
    }
}
