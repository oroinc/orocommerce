<?php

namespace Oro\Bundle\TaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroTaxExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'tax_enable' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'tax_provider' => [
                    'type' => 'text',
                    'value' => BuiltInTaxProvider::NAME
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
                    'value' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN
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
                'origin_address' => ['type' => 'array', 'value' => []],
            ]
        );

        return $treeBuilder;
    }
}
