<?php

namespace OroB2B\Bundle\CheckoutBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_b2b_checkout');

        SettingsBuilder::append(
            $rootNode,
            [
                'frontend_open_orders_separate_page' => [
                    'type' => 'boolean',
                    'value' => false
                ]
            ]
        );

        return $treeBuilder;
    }
}
