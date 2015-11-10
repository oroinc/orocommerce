<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class ActionConfiguration implements ConfigurationInterface
{
    const NODE_ACTION = 'action';

    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();
        return $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root(self::NODE_ACTION);
        $this->addNodes($root->children());

        return $builder;
    }

    /**
     * @param NodeBuilder $builder
     */
    public function addNodes(NodeBuilder $builder)
    {
        $builder
            ->scalarNode('label')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->arrayNode('applications')
                ->prototype('scalar')
                ->end()
            ->end()
            ->scalarNode('extend')
            ->end()
            ->integerNode('extend_priority')
            ->end()
            ->enumNode('extend_strategy')
                ->values(['add', 'replace'])
                ->defaultValue('add')
            ->end()
            ->arrayNode('entities')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('routes')
                ->prototype('scalar')
                ->end()
            ->end()
            ->integerNode('order')
            ->end()
            ->booleanNode('enabled')
                ->defaultTrue()
            ->end()
            ->append($this->getFrontendOptionsNode());
    }

    /**
     * @return NodeDefinition
     */
    protected function getFrontendOptionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('frontend_options');
        $node
            ->children()
                ->scalarNode('icon')->end()
                ->scalarNode('class')->end()
                ->scalarNode('template')->end()
            ->end();

        return $node;
    }
}
