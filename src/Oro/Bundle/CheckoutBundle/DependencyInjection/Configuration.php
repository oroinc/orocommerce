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
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_checkout');

        SettingsBuilder::append(
            $rootNode,
            [
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
                self::DEFAULT_GUEST_CHECKOUT_OWNER => ['type' => 'string', 'value' => null],
            ]
        );

        return $treeBuilder;
    }
}
