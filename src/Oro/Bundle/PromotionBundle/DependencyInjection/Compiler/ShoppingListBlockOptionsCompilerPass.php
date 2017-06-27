<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass add lineItemDiscounts option to list of known options
 * of oro_shopping_list.layout.type.shopping_list_line_items_list block.
 */
class ShoppingListBlockOptionsCompilerPass implements CompilerPassInterface
{
    const SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE = 'oro_shopping_list.layout.type.shopping_list_line_items_list';
    const LINE_ITEM_DISCOUNTS = 'lineItemDiscounts';
    const METHOD_NAME = 'setOptionsConfig';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE)) {
            $definition = $container->getDefinition(self::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE);
            foreach ($definition->getMethodCalls() as $method) {
                if (self::METHOD_NAME === $method[0]) {
                    $options = array_merge(
                        $method[1][0],
                        [self::LINE_ITEM_DISCOUNTS => ['required' => false]]
                    );
                    $definition->removeMethodCall(self::METHOD_NAME);
                    $definition->addMethodCall(self::METHOD_NAME, [$options]);
                }
            }
        }
    }
}
