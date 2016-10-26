<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
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
                ]
            ],
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
