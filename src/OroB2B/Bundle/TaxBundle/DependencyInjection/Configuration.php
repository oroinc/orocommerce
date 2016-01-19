<?php

namespace OroB2B\Bundle\TaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use OroB2B\Bundle\TaxBundle\Provider\BuiltInTaxProvider;

class Configuration implements ConfigurationInterface
{
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
                'tax_enable' => ['value' => true],
                'tax_provider' => ['value' => BuiltInTaxProvider::NAME],
                'start_calculation_with' => ['value' => 'unit_price'],
                'product_prices_include_tax' => ['value' => false],
                'shipping_origin_as_base' => ['value' => []],
                'destination_as_base' => ['value' => []],
                'destination' => ['value' => 'billing_address'],
                'digital_products_us' => ['type' => 'array', 'value' => []],
                'digital_products_eu' => ['type' => 'array', 'value' => []],
            ]
        );

        return $treeBuilder;
    }
}
