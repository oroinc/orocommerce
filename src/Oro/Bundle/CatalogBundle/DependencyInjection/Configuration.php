<?php

namespace Oro\Bundle\CatalogBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_catalog';
    const DIRECT_URL_PREFIX = 'category_direct_url_prefix';
    const ALL_PRODUCTS_PAGE_ENABLED = 'all_products_page_enabled';
    const CATEGORY_IMAGE_PLACEHOLDER = 'category_image_placeholder';
    const SEARCH_AUTOCOMPLETE_MAX_CATEGORIES = 'search_autocomplete_max_categories';
    public const EXCLUDE_CURRENT_BREADCRUMB_ON_ALL_PAGES = 'breadcrumbs_exclude_current_on_all_pages';
    public const REMOVE_SINGLE_BREADCRUMB = 'breadcrumbs_remove_single_breadcrumb';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::DIRECT_URL_PREFIX => ['value' => ''],
                self::ALL_PRODUCTS_PAGE_ENABLED => ['type' => 'boolean', 'value' => false],
                self::CATEGORY_IMAGE_PLACEHOLDER => ['value' => null],
                static::SEARCH_AUTOCOMPLETE_MAX_CATEGORIES => ['type' => 'integer', 'value' => 2],
                self::EXCLUDE_CURRENT_BREADCRUMB_ON_ALL_PAGES => ['value' => false, 'type' => 'boolean'],
                self::REMOVE_SINGLE_BREADCRUMB => ['value' => false, 'type' => 'boolean'],
            ]
        );

        return $treeBuilder;
    }

    /**
     * Returns full key name by it's last part
     *
     * @param $name string last part of the key name (one of the class cons can be used)
     * @return string full config path key
     */
    public static function getConfigKeyByName($name)
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $name;
    }
}
