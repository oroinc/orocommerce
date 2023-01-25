<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\FieldsOptionsProvider;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_GUEST_CHECKOUT_OWNER = 'default_guest_checkout_owner';
    const ENABLE_LINE_ITEMS_GROUPING = 'enable_line_item_grouping';
    const GROUP_LINE_ITEMS_BY = 'group_line_items_by';
    const CREATE_SUBORDERS_FOR_EACH_GROUP = 'create_suborders_for_each_group';
    const SHOW_SUBORDERS_IN_ORDER_HISTORY = 'show_suborders_in_order_history';
    const SHOW_MAIN_ORDERS_IN_ORDER_HISTORY = 'show_main_orders_in_order_history';
    const ENABLE_SHIPPING_METHOD_SELECTION_PER_LINE_ITEM = 'enable_shipping_method_selection_per_line_item';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_checkout');

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'frontend_show_open_orders' => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                'frontend_open_orders_separate_page' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                'guest_checkout' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                'single_page_checkout_increase_performance' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                'registration_allowed' => [
                    'type' => 'boolean',
                    'value' => true,
                ],
                self::DEFAULT_GUEST_CHECKOUT_OWNER => [
                    'type' => 'string',
                    'value' => null,
                ],
                'allow_checkout_without_email_confirmation' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                'checkout_max_line_items_per_page' => [
                    'type' => 'integer',
                    'value' => 1000,
                ],
                'new_checkout_shopping_list_item_changed' => [
                    'type' => 'boolean',
                    'value' => false,
                ],
                self::ENABLE_LINE_ITEMS_GROUPING => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::GROUP_LINE_ITEMS_BY => [
                    'type' => 'string',
                    'value' => FieldsOptionsProvider::DEFAULT_VALUE
                ],
                self::CREATE_SUBORDERS_FOR_EACH_GROUP => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::ENABLE_SHIPPING_METHOD_SELECTION_PER_LINE_ITEM => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::SHOW_SUBORDERS_IN_ORDER_HISTORY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::SHOW_MAIN_ORDERS_IN_ORDER_HISTORY => [
                    'type' => 'boolean',
                    'value' => true
                ],
            ]
        );

        return $treeBuilder;
    }
}
