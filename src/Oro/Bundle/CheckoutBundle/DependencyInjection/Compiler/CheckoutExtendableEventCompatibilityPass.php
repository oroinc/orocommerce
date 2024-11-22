<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\CheckoutBundle\Event\BC\ShoppingListStartExtendableConditionEventFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Contains DI container manipulations required for checkout BC.
 */
class CheckoutExtendableEventCompatibilityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->addShoppingListExtendableEventCompatibility($container);
    }

    private function addShoppingListExtendableEventCompatibility(ContainerBuilder $container): void
    {
        $container->register(
            ShoppingListStartExtendableConditionEventFactory::class,
            ShoppingListStartExtendableConditionEventFactory::class
        );
        $container->getDefinition('oro_action.condition.extendable')
            ->addMethodCall(
                'addEventFactory',
                [
                    'extendable_condition.shopping_list_start',
                    new Reference(ShoppingListStartExtendableConditionEventFactory::class)
                ]
            );
    }
}
