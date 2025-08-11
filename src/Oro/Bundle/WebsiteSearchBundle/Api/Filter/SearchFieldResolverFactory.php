<?php

namespace Oro\Bundle\WebsiteSearchBundle\Api\Filter;

use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver as BaseResolver;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolverFactoryInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * The factory to create {@see SearchFieldResolver}.
 */
class SearchFieldResolverFactory implements SearchFieldResolverFactoryInterface
{
    public function __construct(
        private readonly AbstractSearchMappingProvider $searchMappingProvider,
        private readonly bool $supportEnums = true
    ) {
    }

    #[\Override]
    public function createFieldResolver(string $entityClass, array $fieldMappings): BaseResolver
    {
        return new SearchFieldResolver(
            $this->getSearchFieldMappings($entityClass),
            $fieldMappings,
            $this->supportEnums
        );
    }

    private function getSearchFieldMappings(string $entityClass): array
    {
        $searchFieldMappings = [];
        $entityConfig = $this->searchMappingProvider->getEntityConfig($entityClass);
        if ($entityConfig) {
            $allTextExists = false;
            foreach ($entityConfig['fields'] as $fieldConfig) {
                $fieldType = $fieldConfig['type'];
                $searchFieldMappings[$fieldConfig['name']] = ['type' => $fieldType];
                if (!$allTextExists && SearchQuery::TYPE_TEXT === $fieldType) {
                    $allTextExists = true;
                }
            }
            if ($allTextExists) {
                $searchFieldMappings[SearchIndexer::TEXT_ALL_DATA_FIELD] = ['type' => SearchQuery::TYPE_TEXT];
            }
        }

        return $searchFieldMappings;
    }
}
