<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchProviderPass;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroWebsiteSearchExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'name' => [
                    'type' => 'text',
                    'value' => 'orm'
                ],
                'host' => [
                    'type' => 'text',
                    'value' => 'localhost'
                ],
                'port' => [
                    'type' => 'text',
                    'value' => 9200
                ],
                'username' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'password' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'auth_type' => [
                    'type' => 'text',
                    'value' => 'basic'
                ],
            ]
        );

        return $treeBuilder;
    }
}
