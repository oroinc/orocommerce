<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_web_catalog';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'web_catalog' => ['type' => 'integer', 'value' => null],
                'navigation_root' => ['type' => 'integer', 'value' => null],
                'enable_web_catalog_canonical_url' => ['type' => 'boolean', 'value' => true],
            ]
        );

        return $treeBuilder;
    }
}
