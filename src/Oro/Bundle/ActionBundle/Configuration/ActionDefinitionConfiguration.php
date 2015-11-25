<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use Oro\Bundle\ActionBundle\Model\ActionDefinition;

class ActionDefinitionConfiguration implements ConfigurationInterface
{
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
        $root = $builder->root('configuration');
        $this->addNodes($root);

        return $builder;
    }

    /**
     * @param NodeDefinition $nodeDefinition
     * @return NodeDefinition
     */
    public function addNodes(NodeDefinition $nodeDefinition)
    {
        $nodeDefinition
            ->children()
                ->scalarNode('label')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('applications')
                    ->prototype('scalar')
                    ->end()
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
                    ->defaultValue(0)
                ->end()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->append($this->getFrontendOptionsNode())
            ->end();

        $this->appendFunctionsNodes($nodeDefinition->children());
        $this->appendConditionsNodes($nodeDefinition->children());

        return $nodeDefinition;
    }

    /**
     * @param NodeBuilder $builder
     */
    protected function appendFunctionsNodes(NodeBuilder $builder)
    {
        foreach (ActionDefinition::getAllowedFunctions() as $nodeName) {
            $builder
                ->arrayNode($nodeName)
                    ->prototype('variable')
                    ->end()
                ->end();
        }
    }

    /**
     * @param NodeBuilder $builder
     */
    protected function appendConditionsNodes(NodeBuilder $builder)
    {
        foreach (ActionDefinition::getAllowedConditions() as $nodeName) {
            $builder
                ->arrayNode($nodeName)
                    ->prototype('variable')
                    ->end()
                ->end();
        }
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
