<?php

namespace Oro\Bundle\WarehouseBundle\DependencyInjection;

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

        $rootNode = $treeBuilder->root('oro_warehouse');

        SettingsBuilder::append(
            $rootNode,
            [
                'manage_inventory' => ['value' => false, 'type' => 'boolean'],
            ]
        );

        return $treeBuilder;
    }
}
