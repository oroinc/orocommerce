<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CheckoutStateDiffCompilerPass implements CompilerPassInterface
{
    const CHECKOUT_STATE_DIFF_MANAGER = 'orob2b_checkout.workflow_state.manager.checkout_state_diff';
    const CHECKOUT_STATE_DIFF_MAPPER_TAG = 'checkout.workflow_state.mapper';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHECKOUT_STATE_DIFF_MANAGER)) {
            return;
        }
        $definition = $container->getDefinition(self::CHECKOUT_STATE_DIFF_MANAGER);
        $taggedServices = $container->findTaggedServiceIds(self::CHECKOUT_STATE_DIFF_MAPPER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addMapper',
                [new Reference($id)]
            );
        }
    }
}
