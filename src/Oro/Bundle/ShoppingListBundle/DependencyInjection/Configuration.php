<?php

namespace Oro\Bundle\ShoppingListBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_GUEST_SHOPPING_LIST_OWNER = 'default_guest_shopping_list_owner';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_shopping_list');

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
                'my_shopping_lists_page_enabled' => ['value' => false, 'type' => 'boolean'],
                'my_shopping_lists_all_page_value' => ['value' => 1000, 'type' => 'integer'],
            ]
        );

        return $treeBuilder;
    }
}
