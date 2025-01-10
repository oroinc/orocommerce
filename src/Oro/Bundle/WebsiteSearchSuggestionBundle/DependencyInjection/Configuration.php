<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const SEARCH_AUTOCOMPLETE_MAX_SUGGESTS = 'search_autocomplete_max_suggests';

    public const WEBSITE_SEARCH_SUGGESTION_FEATURE_ENABLED = 'website_search_suggestion_feature_enabled';

    public const ROOT_NODE = 'oro_website_search_suggestion';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            static::SEARCH_AUTOCOMPLETE_MAX_SUGGESTS => ['type' => 'integer', 'value' => 4],
            static::WEBSITE_SEARCH_SUGGESTION_FEATURE_ENABLED => ['type' => 'boolean', 'value' => false],
        ]);

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $key): string
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [self::ROOT_NODE, $key]);
    }
}
