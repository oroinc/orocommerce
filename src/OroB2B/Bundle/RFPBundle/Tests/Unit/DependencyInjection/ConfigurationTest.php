<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }


    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => 1,
                        'default_request_status' => [
                            'value' => 'open',
                            'scope' => 'app'
                        ],
                        'backend_product_visibility' => [
                            'value' => [
                                Product::INVENTORY_STATUS_IN_STOCK,
                                Product::INVENTORY_STATUS_OUT_OF_STOCK
                            ],
                            'scope' => 'app'
                        ],
                        'frontend_product_visibility' => [
                            'value' => [
                                Product::INVENTORY_STATUS_IN_STOCK,
                                Product::INVENTORY_STATUS_OUT_OF_STOCK
                            ],
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
