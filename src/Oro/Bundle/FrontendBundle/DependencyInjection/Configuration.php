<?php

namespace Oro\Bundle\FrontendBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root(OroFrontendExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'frontend_theme' => ['type' => 'string', 'value' => ''],
            ]
        );

        return $treeBuilder;
    }
}
