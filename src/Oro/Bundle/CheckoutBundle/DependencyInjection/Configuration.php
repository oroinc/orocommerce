<?php

namespace Oro\Bundle\CheckoutBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_GUEST_CHECKOUT_OWNER = 'default_guest_checkout_owner';

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
            ]
        );

        return $treeBuilder;
    }
}
