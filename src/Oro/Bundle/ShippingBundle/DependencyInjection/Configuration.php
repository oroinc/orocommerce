<?php

namespace Oro\Bundle\ShippingBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_shipping';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
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
