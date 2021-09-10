<?php

namespace Oro\Bundle\ShippingBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(OroShippingExtension::ALIAS);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'shipping_origin' => ['type' => 'array', 'value' => []],
                'length_units' => ['type' => 'array', 'value' => ['inch', 'foot', 'cm', 'm']],
                'weight_units' => ['type' => 'array', 'value' => ['lbs', 'kg']],
                'freight_classes' => ['type' => 'array', 'value' => ['parcel']]
            ]
        );

        return $treeBuilder;
    }
}
