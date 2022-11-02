<?php

namespace Oro\Bundle\ShoppingListBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_shopping_list';
    const DEFAULT_GUEST_SHOPPING_LIST_OWNER = 'default_guest_shopping_list_owner';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'backend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                'availability_for_guests' => ['type' => 'boolean', 'value' => false],
                self::DEFAULT_GUEST_SHOPPING_LIST_OWNER => ['type' => 'string', 'value' => null],
                'shopping_list_limit' => ['value' => 0, 'type' => 'integer'],
                'mass_adding_on_product_listing_enabled' => ['value' => true, 'type' => 'boolean'],
                'create_shopping_list_for_new_guest' => ['value' => false, 'type' => 'boolean'],
                'shopping_lists_max_line_items_per_page' => ['value' => 1000, 'type' => 'integer'],
                'show_all_in_shopping_list_widget' => ['value' => false, 'type' => 'boolean'],
            ]
        );

        return $treeBuilder;
    }
}
