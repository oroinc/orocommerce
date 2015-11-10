<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ActionListConfiguration implements ConfigurationInterface
{
    const NODE_ACTIONS = 'actions';

    /**
     * @var ActionConfiguration
     */
    protected $configuration;

    /**
     * @param ActionConfiguration $configuration
     */
    public function __construct(ActionConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

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
        $root = $builder->root(self::NODE_ACTIONS);
        $root->useAttributeAsKey('id');
        $this->configuration->addNodes($root->prototype('array')->children());

        return $builder;
    }
}
