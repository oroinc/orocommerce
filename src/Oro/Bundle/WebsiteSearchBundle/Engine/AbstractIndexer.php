<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\Provider\EntityAliasProvider;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
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

    /** @var Mapper */
    protected $mapper;

    /** @var EntityAliasProvider */
    protected $entityAliasProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractSearchMappingProvider $mapper
     * @param EntityAliasProvider $entityAliasProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        AbstractSearchMappingProvider $mapper,
        EntityAliasProvider $entityAliasProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->mapper = $mapper;
        $this->entityAliasProvider = $entityAliasProvider;
    }

    /**
     * Saves index data for batch of entities
     * @param string $entityClass
     * @param array $entitiesData
     * @param string $entityAliasTemp
     * @return int
     */
    abstract protected function saveIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp
    );

    /**
     * Rename old index by aliases to new index
     * @param string $temporaryAlias
     * @param string $currentAlias
     */
    abstract protected function renameIndex($temporaryAlias, $currentAlias);

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, array $context = [])
    {
        $mappingConfig = $this->mapper->getMappingConfig();

        if (!$mappingConfig) {
            throw new \LogicException('Mapping config is empty.');
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

            $collectContextEvent = new CollectContextEvent($websiteContext, $websiteId);
            $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);
            $websiteContext = $collectContextEvent->getContext();
            $websiteContext[self::CONTEXT_WEBSITE_ID_KEY] = $websiteId;

            foreach ($entitiesToIndex as $entityClass => $entityConfig) {
                $handledItems += $this->reindexSingleEntity($entityClass, $entityConfig, $websiteContext);
            }
        }

        return $handledItems;
    }

    /**
     * @param array $context
     * @return array
     */
    protected function getWebsitesToIndex(array $context)
    {
        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            return [$context[self::CONTEXT_WEBSITE_ID_KEY]];
        }

        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);

        return array_map(
            function ($websiteId) {
                return $websiteId['id'];
            },
            $websiteRepository->getAllWebsiteIds()
        );
    }

    /**
     * @param string $entityClass
     * @param array $entityConfig
     * @param array $context
     * @return int
     */
    protected function reindexSingleEntity($entityClass, array $entityConfig, array $context)
    {
        $currentAlias = sprintf('%s_%s', $entityConfig['alias'], $context[self::CONTEXT_WEBSITE_ID_KEY]);
        $temporaryAlias = $this->generateTemporaryAlias($currentAlias);

        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $queryBuilder->select('entity.id');

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        $itemsCount = 0;
        $entityIds = [];
        $realItemsCount = 0;

        foreach ($iterator as $entity) {
            $entityIds[] = $entity['id'];
            $itemsCount++;

            if (0 === $itemsCount % static::BATCH_SIZE) {
                $restrictedEntityIds = $this->restrictIndexEntity($entityIds, $context, $entityClass);
                $realItemsCount += count($restrictedEntityIds);
                $entitiesData = $this->indexEntities($entityClass, $restrictedEntityIds, $context);
                $this->saveIndexData($entityClass, $entitiesData, $temporaryAlias);
                $entityIds = [];
                $entityManager->clear($entityClass);
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $restrictedEntityIds = $this->restrictIndexEntity($entityIds, $context, $entityClass);
            $realItemsCount += count($restrictedEntityIds);
            $entitiesData = $this->indexEntities($entityClass, $restrictedEntityIds, $context);
            $this->saveIndexData($entityClass, $entitiesData, $temporaryAlias);
            $entityManager->clear($entityClass);
        }

        $this->renameIndex($temporaryAlias, $currentAlias);

        return $realItemsCount;
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
        return $entityAlias . '_' . uniqid('website_search', true);
    }

    /**
     * @param array $entityIds
     * @param array $context
     * @param $entityClass
     * @return array
     */
    protected function restrictIndexEntity(array $entityIds, array $context, $entityClass)
    {
        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $entityAlias = $this->entityAliasProvider->getEntityAlias($entityClass);

        $restrictEntitiesEvent = new RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictIndexEntityEvent::NAME, $entityAlias->getAlias()),
            $restrictEntitiesEvent
        );
        $this->eventDispatcher->dispatch(RestrictIndexEntityEvent::NAME, $restrictEntitiesEvent);
        $queryBuilder = $restrictEntitiesEvent->getQueryBuilder();

        $queryBuilder
            ->select('entity.id')
            ->andWhere($queryBuilder->expr()->in('entity.id', $entityIds));

        $result = $queryBuilder->getQuery()->getArrayResult();

        return array_map(
            function ($entityId) {
                return $entityId['id'];
            },
            $result
        );
    }
}
