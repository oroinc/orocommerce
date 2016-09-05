<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroWebsiteSearchExtension::ALIAS);

        $rootNode->children()
            ->scalarNode('engine')
                ->cannotBeEmpty()
                ->defaultValue(SearchConfiguration::DEFAULT_ENGINE)
            ->end()
            ->arrayNode('engine_parameters')
                ->prototype('variable')->end()
            ->end();

        return $treeBuilder;
    }
}
