<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;

class Configuration implements ConfigurationInterface
{
    const ENGINE_KEY = 'engine';
    const ENGINE_PARAMETERS_KEY = 'engine_parameters';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroWebsiteSearchExtension::ALIAS);

        $rootNode->children()
            ->scalarNode(self::ENGINE_KEY)
                ->cannotBeEmpty()
                ->defaultValue(SearchConfiguration::DEFAULT_ENGINE)
            ->end()
            ->arrayNode(self::ENGINE_PARAMETERS_KEY)
                ->prototype('variable')->end()
            ->end();

        return $treeBuilder;
    }
}
