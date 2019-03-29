<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
            ->useAttributeAsKey('name')
            ->prototype('array')
            ->children()
                ->scalarNode('alias')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('fields')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->enumNode('type')
                                ->values($this->fieldTypes)
                            ->end()
                            ->booleanNode('store')->end()
                            ->booleanNode('default_search_field')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
