<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_inventory';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();
        SettingsBuilder::append(
            $rootNode,
            [
                'manage_inventory' => ['type' => 'boolean', 'value' => false],
                'highlight_low_inventory' => ['type' => 'boolean', 'value' => false],
                'inventory_threshold' => ['type' => 'decimal', 'value' => 0],
                'low_inventory_threshold' => ['type' => 'decimal', 'value' => 0],
                'backorders' => ['type' => 'boolean', 'value' => false],
                'decrement_inventory' => ['type' => 'boolean', 'value' => true],
                'minimum_quantity_to_order' => ['type' => 'decimal', 'value' => null],
                'maximum_quantity_to_order' => ['type' => 'decimal', 'value' => 100000],
                'hide_labels_past_availability_date' => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
