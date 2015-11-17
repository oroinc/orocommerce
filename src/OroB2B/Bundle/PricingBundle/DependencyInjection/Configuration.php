<?php


namespace OroB2B\Bundle\PricingBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_PRICE_LISTS = 'default_price_lists';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BPricingExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::DEFAULT_PRICE_LISTS => [ 'type' => 'array', 'value' => []]
            ]
        );

        return $treeBuilder;
    }
}
