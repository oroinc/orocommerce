<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $configuration->getConfigTreeBuilder()
        );
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $expected = [
            'settings' => [
                'resolved' => true,
                'tax_enable' => [
                    'value' => true,
                    'scope' => 'app'
                ],
                'tax_provider' => [
                    'value' => 'built_in',
                    'scope' => 'app'
                ],
                'start_calculation_with' => [
                    'value' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE,
                    'scope' => 'app'
                ],
                'start_calculation_on' => [
                    'value' => TaxationSettingsProvider::START_CALCULATION_ON_TOTAL,
                    'scope' => 'app'
                ],
                'product_prices_include_tax' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'use_as_base_by_default' => [
                    'value' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                    'scope' => 'app'
                ],
                'use_as_base_exclusions' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'destination' => [
                    'value' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                    'scope' => 'app'
                ],
                'digital_products_us' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'digital_products_eu' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'origin_address' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'shipping_tax_code' => [
                    'value' => [],
                    'scope' => 'app'
                ],
                'shipping_rates_include_tax' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'address_resolver_granularity' => [
                    'value' =>  AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_ZIP,
                    'scope' => 'app'
                ],
                'calculate_taxes_after_promotions' => [
                    'value' => false,
                    'scope' => 'app'
                ],
            ],
        ];

        self::assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
