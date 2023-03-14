<?php

namespace Oro\Bundle\TaxBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_tax';
    public const ADDRESS_RESOLVER_GRANULARITY = 'address_resolver_granularity';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'tax_enable' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'tax_provider' => [
                    'type' => 'text',
                    'value' => 'built_in'
                ],
                'start_calculation_with' => [
                    'type' => 'text',
                    'value' => TaxationSettingsProvider::START_CALCULATION_UNIT_PRICE
                ],
                'start_calculation_on' => [
                    'type' => 'text',
                    'value' => TaxationSettingsProvider::START_CALCULATION_ON_TOTAL
                ],
                'product_prices_include_tax' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'use_as_base_by_default' => [
                    'type' => 'text',
                    'value' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN
                ],
                'use_as_base_exclusions' => [
                    'type' => 'array',
                    'value' => []
                ],
                'destination' => [
                    'value' => TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS
                ],
                'digital_products_us' => [
                    'type' => 'array',
                    'value' => []
                ],
                'digital_products_eu' => [
                    'type' => 'array',
                    'value' => []
                ],
                'origin_address' => [
                    'type' => 'array',
                    'value' => []
                ],
                'shipping_tax_code' => [
                    'type' => 'array',
                    'value' => []
                ],
                'shipping_rates_include_tax' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'address_resolver_granularity' => [
                    'type'  =>  'text',
                    'value' =>  AddressResolverSettingsProvider::ADDRESS_RESOLVER_GRANULARITY_ZIP
                ],
                'calculate_taxes_after_promotions' => [
                    'type' => 'boolean',
                    'value' => false
                ],
            ]
        );

        return $treeBuilder;
    }
}
