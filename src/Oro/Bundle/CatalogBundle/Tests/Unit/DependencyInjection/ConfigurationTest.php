<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CatalogBundle\DependencyInjection\Configuration;
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
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                'category_direct_url_prefix' => [
                    'value' => '',
                    'scope' => 'app'
                ],
                'all_products_page_enabled' => [
                    'value' => false,
                    'scope' => 'app',
                ],
                'category_image_placeholder' => [
                    'value' => null,
                    'scope' => 'app',
                ]
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
