<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
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
                        ],
                        'notify_assigned_sales_reps_of_the_customer' => [
                            'value' => 'always',
                            'scope' => 'app',
                        ],
                        'notify_owner_of_customer_user_record' => [
                            'value' => 'always',
                            'scope' => 'app',
                        ],
                        'notify_owner_of_customer' => [
                            'value' => 'always',
                            'scope' => 'app',
                        ],
                        'feature_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'frontend_feature_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'guest_rfp' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        Configuration::DEFAULT_GUEST_RFP_OWNER => [
                            'value' => null,
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
