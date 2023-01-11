<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration as SearchConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ENGINE_KEY = 'engine';
    public const ENGINE_PARAMETERS_KEY = 'engine_parameters';
    public const INDEXER_BATCH_SIZE = 'indexer_batch_size';

    public const INDEXER_BATCH_SIZE_DEFAULT = 100;

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(OroWebsiteSearchExtension::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode(self::ENGINE_KEY)
                ->cannotBeEmpty()
                ->defaultValue(SearchConfiguration::DEFAULT_ENGINE)
            ->end()
            ->arrayNode(self::ENGINE_PARAMETERS_KEY)
                ->prototype('variable')->end()
            ->end()
            ->scalarNode(self::INDEXER_BATCH_SIZE)
                ->defaultValue(self::INDEXER_BATCH_SIZE_DEFAULT)
                ->validate()
                    ->always(
                        function ($v) {
                            if (!is_int($v) || $v < 1 || $v > 100) {
                                throw new \InvalidArgumentException(
                                    'Expected an integer between 1 and 100.'
                                );
                            }

                            return $v;
                        }
                    )
            ->end()
        ;

        return $treeBuilder;
    }
}
