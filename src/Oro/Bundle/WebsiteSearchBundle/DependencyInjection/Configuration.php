<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ENGINE_KEY_DSN = 'engine_dsn';
    public const ENGINE_PARAMETERS_KEY = 'engine_parameters';
    public const INDEXER_BATCH_SIZE = 'indexer_batch_size';

    public const INDEXER_BATCH_SIZE_DEFAULT = 100;
    public const INDEXER_BATCH_SIZE_MIN = 1;
    public const INDEXER_BATCH_SIZE_MAX = 100;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_website_search');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode(self::ENGINE_KEY_DSN)
                ->cannotBeEmpty()
                ->defaultValue(SearchConfiguration::DEFAULT_ENGINE_DSN)
            ->end()
            ->arrayNode(self::ENGINE_PARAMETERS_KEY)
                ->prototype('variable')->end()
            ->end()
            ->integerNode(self::INDEXER_BATCH_SIZE)
                ->defaultValue(self::INDEXER_BATCH_SIZE_DEFAULT)
                ->min(self::INDEXER_BATCH_SIZE_MIN)
                ->max(self::INDEXER_BATCH_SIZE_MAX)
            ->end()
        ;

        SettingsBuilder::append(
            $rootNode,
            [
                'enable_global_search_history_feature' => ['type' => 'boolean', 'value' => false],
                'enable_global_search_history_tracking' => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
