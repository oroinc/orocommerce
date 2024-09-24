<?php

namespace Oro\Bundle\ProductBundle\Menu\Frontend;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;

/**
 * Adds "Quick Order Form" to the storefront menu.
 */
class QuickAddMenuBuilder implements BuilderInterface
{
    private ComponentProcessorRegistry $processorRegistry;

    public function __construct(ComponentProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    #[\Override]
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        if (!$this->processorRegistry->hasAllowedProcessors()) {
            return;
        }

        $menu
            ->addChild(
                'oro.product.frontend.quick_add.title',
                [
                    'route' => 'oro_product_frontend_quick_add',
                    'extras' => [
                        'position' => 500,
                        'description' => 'oro.product.frontend.quick_add.description',
                    ],
                ]
            );
    }
}
