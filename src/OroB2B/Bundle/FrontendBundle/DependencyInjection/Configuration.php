<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root(OroB2BFrontendExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'frontend_theme' => ['type' => 'string', 'value' => ''],
            ]
        );

        return $treeBuilder;
    }
}
