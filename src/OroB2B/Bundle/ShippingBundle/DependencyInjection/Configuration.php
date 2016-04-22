<?php

namespace OroB2B\Bundle\ShippingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BShippingExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'shipping_origin' => ['type' => 'array', 'value' => []],
            ]
        );
        
        return $treeBuilder;
    }
}
