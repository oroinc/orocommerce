<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const ENGINE_KEY   = 'engine';
    const ENGINE_PARAMETERS_KEY   = 'engine_parameters';
    const DEFAULT_ENGINE = 'orm';

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
                ->defaultValue(self::DEFAULT_ENGINE)
            ->end()
            ->arrayNode('engine_parameters')
                ->prototype('variable')->end()
            ->end();

        return $treeBuilder;
    }
}
