<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass add lineItemDiscounts option to list of known options
 * of oro_shopping_list.layout.type.shopping_list_line_items_list
 * and oro_checkout.layout.block_type.checkout_order_summary_line_items
 * block.
 */
class LayoutBlockOptionsCompilerPass implements CompilerPassInterface
{
    const SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE = 'oro_shopping_list.layout.type.shopping_list_line_items_list';
    const CHECKOUT_LINE_ITEMS_BLOCK_SERVICE = 'oro_checkout.layout.block_type.checkout_order_summary_line_items';
    const LINE_ITEM_DISCOUNTS = 'lineItemDiscounts';
    const METHOD_NAME = 'setOptionsConfig';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->addLineItemDiscountsOption($container, self::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE);
        $this->addLineItemDiscountsOption($container, self::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $service
     */
    private function addLineItemDiscountsOption(ContainerBuilder $container, $service)
    {
        if ($container->hasDefinition($service)) {
            $definition = $container->getDefinition($service);
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
