<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\SearchBundle\Query\Query;

class MappingConfiguration implements ConfigurationInterface
{
    /**
     * @var array
     */
    protected $fieldTypes = [
        Query::TYPE_TEXT,
        Query::TYPE_DECIMAL,
        Query::TYPE_INTEGER,
        Query::TYPE_DATETIME
    ];

    /**
     * Website search mapping configuration structure
     *
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mappings');

        $rootNode
            ->children()
                ->arrayNode('mappings')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('alias')
                    ->end()
                    ->arrayNode('fields')
                        ->defaultValue([])
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')->end()
                                ->enumNode('type')
                                    ->values($this->fieldTypes)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
