<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractIndexer implements IndexerInterface
{
    const BATCH_SIZE = 100;
    const CONTEXT_WEBSITE_ID_KEY = 'website_id';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        $mappingConfig = $this->mappingProvider->getMappingConfig();

        if (!$mappingConfig) {
            // @todo: throw exception?
            return 0;
        }

        if ($class) {
            if (!isset($mappingConfig[$class])) {
                throw new \InvalidArgumentException('There is no such entity in mapping config.');
            }
            $entitiesToIndex[$class] = $mappingConfig[$class];
        } else {
            $entitiesToIndex = $mappingConfig;
        }

        $websitesToIndex = $this->getWebsitesToIndex($context);

        return $this->reindexEntities($websitesToIndex, $entitiesToIndex, $context);
    }

    /**
     * @param array $websiteIdsToIndex
     * @param array $entitiesToIndex
     * @param array $context
     * @return int
     */
    protected function reindexEntities(array $websiteIdsToIndex, array $entitiesToIndex, array $context)
    {
        $handledItems = 0;
        foreach ($websiteIdsToIndex as $websiteId) {
            $websiteContext = $context;
            $websiteContext[self::CONTEXT_WEBSITE_ID_KEY] = $websiteId;

            $collectContextEvent = new CollectContextEvent($websiteContext);
            $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);
            $websiteContext = $collectContextEvent->getContext();

            foreach ($entitiesToIndex as $entityClass => $entityConfig) {
                $handledItems += $this->reindexSingleEntity($entityClass, $entityConfig, $websiteContext);
            }
        }

        return $handledItems;
    }

    /**
     * Rename old index by aliases to new index
     *
     * @param string $oldAlias
     * @param string $newAlias
     */
    abstract protected function renameIndex($oldAlias, $newAlias);

    /**
     * @param array $context
     * @return array
     */
    protected function getWebsitesToIndex(array $context)
    {
        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            return $context[self::CONTEXT_WEBSITE_ID_KEY];
        }

        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        return $websiteRepository->getAllWebsiteIds();
    }

    /**
     * @param string $entityClass
     * @param array $entityConfig
     * @param array $context
     * @return int
     */
    protected function reindexSingleEntity($entityClass, array $entityConfig, array $context)
    {
        $entityAlias = $this->applyPlaceholders($entityConfig['alias'], $context);
        $entityAliasTemp = $this->generateTemporaryAlias($entityAlias);

        $entityManager = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        $entityRepository = $entityManager->getRepository($entityClass);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');

        $restrictEntitiesEvent = new RestrictIndexEntitiesEvent($queryBuilder, $entityClass, $context);
        $this->eventDispatcher->dispatch(RestrictIndexEntitiesEvent::NAME, $restrictEntitiesEvent);
        $queryBuilder = $restrictEntitiesEvent->getQueryBuilder();

        $queryBuilder->select('entity.id');
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        $itemsCount = 0;
        $entityIds = [];
        foreach ($iterator as $entity) {
            $entityIds[] = $entity['id'];
            $itemsCount++;

            if (0 === $itemsCount % static::BATCH_SIZE) {
                $entitiesData = $this->indexEntities($entityClass, $entityIds, $context);
                $this->saveIndexData($entityClass, $entityIds, $entitiesData, $entityAliasTemp, $context);
                $entityManager->clear();
                $entityIds = [];
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $entitiesData = $this->indexEntities($entityClass, $entityIds, $context);
            $this->saveIndexData($entityClass, $entityIds, $entitiesData, $entityAliasTemp, $context);
            $entityManager->clear();
        }

        $this->renameIndex($entityAliasTemp, $entityAlias);

        return $itemsCount;
    }

    /**
     * @todo: Use some provider to replace placeholders
     * @param string $alias
     * @param array $context
     * @return mixed
     */
    protected function applyPlaceholders($alias, $context)
    {
        $replaceTo = sprintf('website_%d', $context[self::CONTEXT_WEBSITE_ID_KEY]);

        return str_replace('WEBSITE_ID', $replaceTo, $alias);
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @param array $context
     * @return array
     */
    protected function indexEntities($entityClass, array $entityIds, array $context)
    {
        $indexEntityEvent = new IndexEntityEvent($entityClass, $entityIds, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);
        return $indexEntityEvent->getEntitiesData();
    }

    /**
     * @todo Move this logic to to mapper provider
     * @param string $entityAlias
     * @return string
     */
    protected function generateTemporaryAlias($entityAlias)
    {
        return $entityAlias . '_' . time();
    }

    /**
     * Saves index data for batch of entities
     *
     * @param string $entityClass
     * @param array $entityIds
     * @param array $entitiesData
     * @param string $entityAliasTemp
     * @param array $context
     * @return
     */
    abstract protected function saveIndexData(
        $entityClass,
        array $entityIds,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    );
}
