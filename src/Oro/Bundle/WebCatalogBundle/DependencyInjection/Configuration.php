<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroWebCatalogExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'web_catalog' => ['type' => 'integer', 'value' => null]
            ]
        );

        return $treeBuilder;
    }
}
