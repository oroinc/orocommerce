<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_checkout';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'frontend_show_open_orders' => ['type' => 'boolean', 'value' => true],
                'frontend_open_orders_separate_page' => ['type' => 'boolean', 'value' => false],
                'guest_checkout' => ['type' => 'boolean', 'value' => false],
                'single_page_checkout_increase_performance' => ['type' => 'boolean', 'value' => false],
                'registration_allowed' => ['type' => 'boolean', 'value' => true],
                'default_guest_checkout_owner' => ['type' => 'string', 'value' => null],
                'allow_checkout_without_email_confirmation' => ['type' => 'boolean', 'value' => false],
                'checkout_max_line_items_per_page' => ['type' => 'integer', 'value' => 1000],
                'enable_line_item_grouping' => ['type' => 'boolean', 'value' => false],
                'group_line_items_by' => ['type' => 'string', 'value' => 'product.category'],
                'create_suborders_for_each_group' => ['type' => 'boolean', 'value' => false],
                'enable_shipping_method_selection_per_line_item' => ['type' => 'boolean', 'value' => false],
                'show_suborders_in_order_history' => ['type' => 'boolean', 'value' => true],
                'show_main_orders_in_order_history' => ['type' => 'boolean', 'value' => true],
            ]
        );

        return $treeBuilder;
    }
}
