<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const ENGINE_KEY   = 'engine';
    const ENGINE_PARAMETERS_KEY   = 'engine_parameters';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroWebsiteSearchExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::ENGINE_KEY => [
                    'type' => 'text',
                    'value' => 'orm'
                ],
                self::ENGINE_PARAMETERS_KEY => [
                    'type' => 'array',
                    'value' => []
                ]
            ]
        );

        return $treeBuilder;
    }
}
