<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $this->assertEquals(
            [
                'settings' => [
                    'resolved' => true,
                    'backend_product_visibility' => [
                        'value' => [
                            Product::INVENTORY_STATUS_IN_STOCK,
                            Product::INVENTORY_STATUS_OUT_OF_STOCK
                        ],
                        'scope' => 'app'
                    ],
                    Configuration::CONTACT_INFO_SOURCE_DISPLAY => [
                        'value' => ContactInfoSourceOptionsProvider::DONT_DISPLAY,
                        'scope' => 'app'
                    ],
                    Configuration::CONTACT_DETAILS => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::ALLOW_USER_CONFIGURATION => [
                        'value' => true,
                        'scope' => 'app'
                    ],
                    Configuration::AVAILABLE_USER_OPTIONS => [
                        'value' => [],
                        'scope' => 'app'
                    ],
                    Configuration::CONTACT_INFO_USER_OPTION => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::CONTACT_INFO_MANUAL_TEXT => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::GUEST_CONTACT_INFO_TEXT => [
                        'value' => '',
                        'scope' => 'app'
                    ],
                    Configuration::ENABLE_GUEST_QUOTE => [
                        'value' => false,
                        'scope' => 'app'
                    ],
                    'quote_frontend_feature_enabled' => [
                        'value' => true,
                        'scope' => 'app'
                    ],
                ],
            ],
            $processor->processConfiguration($configuration, [])
        );
    }
}
