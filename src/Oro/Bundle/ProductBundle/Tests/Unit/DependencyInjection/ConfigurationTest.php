<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(TreeBuilder::class, $configuration->getConfigTreeBuilder());
    }

    public function testGetConfigKeyByName()
    {
        $key = 'options';
        $configKey = Configuration::getConfigKeyByName($key);
        static::assertEquals('oro_product.'.$key, $configKey);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected = [
            'settings' => [
                'resolved' => 1,
                'unit_rounding_type' => [
                    'value' => RoundingServiceInterface::ROUND_HALF_UP,
                    'scope' => 'app'
                ],
                'single_unit_mode' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'single_unit_mode_show_code' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'default_unit' => [
                    'value' => 'each',
                    'scope' => 'app'
                ],
                'default_unit_precision' => [
                    'value' => 0,
                    'scope' => 'app'
                ],
                'general_frontend_product_visibility' => [
                    'value' => [
                        Product::INVENTORY_STATUS_IN_STOCK,
                        Product::INVENTORY_STATUS_OUT_OF_STOCK
                    ],
                    'scope' => 'app'
                ],
                'product_image_watermark_file' => [
                    'value' => null,
                    'scope' => 'app'
                ],
                'product_image_watermark_size' => [
                    'value' => 100,
                    'scope' => 'app'
                ],
                'product_image_watermark_position' => [
                    'value' => 'center',
                    'scope' => 'app'
                ],
                'enable_quick_order_form' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'product_direct_url_prefix' => [
                    'value' => '',
                    'scope' => 'app'
                ]
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
