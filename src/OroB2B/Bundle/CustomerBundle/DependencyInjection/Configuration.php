<?php

namespace OroB2B\Bundle\CustomerBundle\DependencyInjection;

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
        $rootNode    = $treeBuilder->root(OroB2BCustomerExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'registration_allowed' => ['type' => 'boolean', 'value' => true],
                'confirmation' => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
