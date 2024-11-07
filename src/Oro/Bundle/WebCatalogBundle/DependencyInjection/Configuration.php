<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_web_catalog';
    public const NAVIGATION_ROOT = 'navigation_root';
    public const EMPTY_SEARCH_RESULT_PAGE = 'empty_search_result_page';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'web_catalog' => ['type' => 'integer', 'value' => null],
                'navigation_root' => ['type' => 'integer', 'value' => null],
                'enable_web_catalog_canonical_url' => ['type' => 'boolean', 'value' => true],
                self::EMPTY_SEARCH_RESULT_PAGE => ['type' => 'integer', 'value' => null],
            ]
        );

        return $treeBuilder;
    }
}
