<?php

namespace Oro\Bundle\ShoppingListBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_QUEST_SHOPPING_LIST_OWNER = 'default_guest_shopping_list_owner';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_shopping_list');

        SettingsBuilder::append(
            $rootNode,
            [
                'backend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ]
                ],
                self::DEFAULT_QUEST_SHOPPING_LIST_OWNER => ['type' => 'string', 'value' => null],
            ]
        );

        return $treeBuilder;
    }
}
