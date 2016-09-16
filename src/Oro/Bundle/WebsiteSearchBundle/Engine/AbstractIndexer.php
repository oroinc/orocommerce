<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractIndexer implements IndexerInterface
{
    const BATCH_SIZE = 10;
    const CONTEXT_WEBSITE_ID_KEY = 'website_id';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->entityAliasResolver = $entityAliasResolver;
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
        $entitiesToIndex = $this->getEntitiesToIndex($class);
        $websitesToIndex = $this->getWebsitesToIndex($context);
        $handledItems = 0;

        foreach ($websitesToIndex as $websiteId) {
            $websiteContext = $this->collectContextForWebsite($websiteId, $context);
            foreach ($entitiesToIndex as $entityClass) {
                $handledItems += $this->reindexSingleEntity($entityClass, $websiteContext);
            }
        }

        return $handledItems;
    }

    /**
     * @param string $class
     * @throws \InvalidArgumentException
     */
    private function ensureEntityClassIsSupported($class)
    {
        if (!$this->mappingProvider->isClassSupported($class)) {
            throw new \InvalidArgumentException('There is no such entity in mapping config.');
        }
    }

    /**
     * @param string $class
     * @return array
     * @throws \InvalidArgumentException|\LogicException
     */
    private function getEntitiesToIndex($class = null)
    {
        if ($class) {
            $this->ensureEntityClassIsSupported($class);

            $entityClasses = [$class];
        } else {
            $entityClasses = $this->mappingProvider->getEntityClasses();

            if (empty($entityClasses)) {
                throw new \LogicException('Mapping config is empty.');
            }
        }

        return $entityClasses;
    }

    /**
     * {@inheritdoc}
     */
    public function save($entityOrEntities, array $context = [])
    {
        $entities = is_array($entityOrEntities) ? $entityOrEntities: [$entityOrEntities];

        $entitiesByClass = [];
        foreach ($entities as $entity) {
            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $entitiesByClass[$entityClass][] = $entity;
        }

        foreach (array_keys($entitiesByClass) as $entityClass) {
            $this->ensureEntityClassIsSupported($entityClass);
        }

        $this->delete($entities, $context);
        $websitesToIndex = $this->getWebsitesToIndex($context);

        foreach ($websitesToIndex as $websiteId) {
            $websiteContext = $this->collectContextForWebsite($websiteId, $context);

            foreach ($entitiesByClass as $entityClass => $entities) {
                $entityAlias = $this->mappingProvider->getEntityAlias($entityClass);
                $currentAlias = $this->applyPlaceholders($entityAlias, $websiteContext);

                $ids = [];
                foreach ($entities as $entity) {
                    $ids[] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
                }

                $this->indexEntities($entityClass, $ids, $websiteContext, $currentAlias);
            }
        }

        return true;
    }

    /**
     * @param int $websiteId
     * @param array $context
     * @return array
     */
    protected function collectContextForWebsite($websiteId, array $context)
    {
        $context[self::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
        $collectContextEvent = new CollectContextEvent($context);
        $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);

        return $collectContextEvent->getContext();
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

        return $websiteRepository->getWebsiteIdentifiers();
    }

    /**
     * @param string $entityClass
     * @param array $context
     * @return int
     */
    protected function reindexSingleEntity($entityClass, array $context)
    {
        $currentAlias = $this->applyPlaceholders(
            $this->mappingProvider->getEntityAlias($entityClass),
            $context
        );

        $temporaryAlias = $this->generateTemporaryAlias($currentAlias);

        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);

        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
        $queryBuilder->select("entity.$identifierName as id");

        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(static::BATCH_SIZE);

        $itemsCount = 0;
        $entityIds = [];
        $realItemsCount = 0;

        foreach ($iterator as $entity) {
            $entityIds[] = $entity['id'];
            $itemsCount++;
            if (0 === $itemsCount % static::BATCH_SIZE) {
                $realItemsCount += $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
                $entityIds = [];
                $entityManager->clear($entityClass);
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $realItemsCount += $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
            $entityManager->clear($entityClass);
        }

        $this->renameIndex($temporaryAlias, $currentAlias);

        return $realItemsCount;
    }

    /**
     * @todo: Use some provider to replace placeholders
     * @param string $alias
     * @param array $context
     * @return mixed
     */
    protected function applyPlaceholders($alias, $context)
    {
        return str_replace('WEBSITE_ID', $context[self::CONTEXT_WEBSITE_ID_KEY], $alias);
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @param array $context
     * @param string $aliasToSave
     * @return int
     */
    protected function indexEntities($entityClass, array $entityIds, array $context, $aliasToSave)
    {
        $restrictedEntityIds = $this->restrictIndexEntity($entityIds, $context, $entityClass);

        if (!$restrictedEntityIds) {
            return 0;
        }

        $indexEntityEvent = new IndexEntityEvent($entityClass, $restrictedEntityIds, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);
        $entitiesData = $indexEntityEvent->getEntitiesData();

        return $this->saveIndexData($entityClass, $entitiesData, $aliasToSave);
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
     * @param string $entityClass
     * @return array
     */
    protected function restrictIndexEntity(array $entityIds, array $context, $entityClass)
    {
        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictIndexEntityEvent::NAME, $entityAlias),
            $restrictEntitiesEvent
        );
        $this->eventDispatcher->dispatch(RestrictIndexEntityEvent::NAME, $restrictEntitiesEvent);
        $queryBuilder = $restrictEntitiesEvent->getQueryBuilder();

        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);

        $queryBuilder
            ->select("entity.$identifierName as id")
            ->andWhere($queryBuilder->expr()->in("entity.$identifierName", ':entityIds'));

        $queryBuilder->setParameter('entityIds', $entityIds);

        $result = $queryBuilder->getQuery()->getArrayResult();

        return array_column($result, 'id');
    }
}
