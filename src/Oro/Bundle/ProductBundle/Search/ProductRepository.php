<?php
declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Website search engine repository for OroProductBundle:Product entity
 * This repository encapsulates Product related operations
 */
class ProductRepository extends WebsiteSearchRepository
{
    protected EventDispatcherInterface $eventDispatcher;
    protected ConfigManager $configManager;

    public function __construct(
        QueryFactoryInterface $queryFactory,
        AbstractSearchMappingProvider $mappingProvider,
        EventDispatcherInterface $eventDispatcher,
        ConfigManager $configManager
    ) {
        parent::__construct($queryFactory, $mappingProvider);

        $this->eventDispatcher = $eventDispatcher;
        $this->configManager = $configManager;
    }

    /**
     * @param SearchQueryInterface|null $query
     * @param string                    $aggregateAlias
     * @return SearchQueryInterface
     */
    public function getFamilyAttributeCountsQuery(
        SearchQueryInterface $query = null,
        $aggregateAlias = 'familyAttributesCount'
    ): SearchQueryInterface {
        if (!$query) {
            $query = $this->createQuery();
        } else {
            $query = clone $query;
        }

        // reset query parts to make it work as fast as possible
        $query->getQuery()->select([]);
        $query->getQuery()->getCriteria()->orderBy([]);
        $query->setFirstResult(0);
        $query->setMaxResults(1);

        // calculate category counts
        $query->addAggregate(
            $aggregateAlias,
            'integer.attribute_family_id',
            Query::AGGREGATE_FUNCTION_COUNT
        );

        return $query;
    }

    /**
     * @param int $id
     * @return Item|null
     */
    public function findOne($id): ?Item
    {
        $searchQuery = $this->createQuery()->addWhere(
            Criteria::expr()->eq('integer.system_entity_id', $id)
        );

        $items = $searchQuery->getResult();

        if ($items->getRecordsCount() < 1) {
            return null;
        }

        return $items->getElements()[0];
    }

    /**
     * @param array $skus
     * @return SearchQueryInterface
     */
    public function getFilterSkuQuery($skus): SearchQueryInterface
    {
        $searchQuery = $this->createQuery();

        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map('mb_strtoupper', $skus);

        $searchQuery
            ->addSelect('sku')
            ->addSelect('names_LOCALIZATION_ID as name')
            ->addSelect('integer.system_entity_id as product_id')
            ->addWhere(Criteria::expr()->in('sku_uppercase', $upperCaseSkus));

        return $searchQuery;
    }

    /**
     * @param array $skus
     * @return Item[]
     */
    public function searchFilteredBySkus(array $skus): array
    {
        $searchQuery = $this->getFilterSkuQuery($skus);

        return $searchQuery->getResult()->getElements();
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return SearchQueryInterface
     */
    public function getSearchQuery($search, $firstResult, $maxResults): SearchQueryInterface
    {
        $searchQuery = $this->createQuery();

        $searchQuery
            ->addSelect('sku')
            ->addSelect('names_LOCALIZATION_ID as name')
            ->addWhere(Criteria::expr()->contains('all_text_LOCALIZATION_ID', $search))
            ->setOrderBy('integer.system_entity_id', Criteria::ASC)
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $searchQuery;
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return Item[]
     */
    public function findBySkuOrName($search, $firstResult = 0, $maxResults = null): array
    {
        $query = $this->getSearchQueryBySkuOrName($search, $firstResult, $maxResults);

        return $query->getResult()->getElements();
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return SearchQueryInterface
     */
    public function getSearchQueryBySkuOrName($search, $firstResult = 0, $maxResults = null): SearchQueryInterface
    {
        $operator = $this->getProductSearchOperator();

        $query = $this->createQuery();
        $query
            ->setFrom('oro_product_WEBSITE_ID')
            ->addSelect('sku')
            ->addSelect('integer.system_entity_id as product_id')
            ->addSelect('names_LOCALIZATION_ID as name')
            ->getCriteria()
            ->andWhere(
                Criteria::expr()->orX(
                    Criteria::expr()->$operator('sku_uppercase', mb_strtoupper($search)),
                    Criteria::expr()->$operator('names_LOCALIZATION_ID', $search)
                )
            )
            ->setFirstResult((int)$firstResult);

        if ($maxResults !== null) {
            $query->setMaxResults($maxResults);
        }

        return $query;
    }

    public function getAutocompleteSearchQuery(string $queryString, int $numberOfProducts): SearchQueryInterface
    {
        $fields = [
            'text.sku',
            'text.names_LOCALIZATION_ID as name',
            'text.image_product_small as image',
            'text.inv_status as inventory_status',
        ];

        $event = new CollectAutocompleteFieldsEvent($fields);
        $this->eventDispatcher->dispatch($event);

        $operator = $this->getProductSearchOperator();

        return $this->createQuery()
            ->addSelect($event->getFields())
            ->setFrom('oro_product_WEBSITE_ID')
            ->addWhere(Criteria::expr()->$operator('text.all_text_LOCALIZATION_ID', $queryString))
            ->setMaxResults($numberOfProducts);
    }

    /**
     * @return string "contains" or "like"
     */
    public function getProductSearchOperator(): string
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_PARTIAL_PRODUCT_SEARCH);

        return $this->configManager->get($key) ? 'like' : 'contains';
    }
}
