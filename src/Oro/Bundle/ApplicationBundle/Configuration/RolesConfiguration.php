<?php

namespace Oro\Bundle\ApplicationBundle\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class RolesConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('roles');
        $rootNode
            ->useAttributeAsKey('name')
            ->validate()
                ->always(
                    function ($roles) {
                        $invalid = [];

                        foreach ($roles as $name => $params) {
                            if (!preg_match('/^ROLE_/', $name)) {
                                $invalid[] = $name;
                            }
                        }

                        if (count($invalid)) {
                            throw new InvalidConfigurationException(
                                sprintf(
                                    'Configuration contains roles with invalid name "%s". ' .
                                    'Role name should begin with the prefix \'ROLE_\'.',
                                    implode(', ', $invalid)
                                )
                            );
                        }

                        return $roles;
                    }
                )
            ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('description')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
