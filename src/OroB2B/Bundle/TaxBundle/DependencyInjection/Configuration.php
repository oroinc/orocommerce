<?php

namespace OroB2B\Bundle\TaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_START_CALCULATION_WITH = 'unit_price';
    const DEFAULT_DESTINATION = 'billing_address';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BTaxExtension::ALIAS);

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
                    'value' => self::DEFAULT_START_CALCULATION_WITH
                ],
                'product_prices_include_tax' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'use_as_base_by_default' => [
                    'type' => 'text',
                    'value' => TaxBaseExclusion::USE_AS_BASE_SHIPPING_ORIGIN
                ],
                'use_as_base_exclusions' => [
                    'type' => 'array',
                    'value' => []
                ],
                'destination' => [
                    'value' => self::DEFAULT_DESTINATION
                ],
                'digital_products_us' => [
                    'type' => 'array',
                    'value' => []
                ],
                'digital_products_eu' => [
                    'type' => 'array',
                    'value' => []
                ],
            ]
        );

        return $treeBuilder;
    }
}
