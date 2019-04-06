<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();

        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $expected = [
            'settings' => [
                'resolved' => true,
                'manage_inventory' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'highlight_low_inventory' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'inventory_threshold' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'low_inventory_threshold' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'backorders' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'decrement_inventory' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'minimum_quantity_to_order' => [
                    'scope' => 'app',
                    'value' => null,
                ],
                'maximum_quantity_to_order' => [
                    'scope' => 'app',
                    'value' => Configuration::DEFAULT_MAXIMUM_QUANTITY_TO_ORDER,
                ],
                'hide_labels_past_availability_date' => [
                    'scope' => 'app',
                    'value' => true
                ]
            ],
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
