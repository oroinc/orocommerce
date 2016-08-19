<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;

use OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractIndexer implements IndexerInterface
{
    const BATCH_SIZE = 100;
    const CONTEXT_WEBSITE_ID_KEY = 'website_id';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var MapperStub */
    protected $mapper;

    /** @var array */
    protected $indexerContext;

    /** @var array */
    protected $processedEntityAliases;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param MapperStub $mapper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        MapperStub $mapper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function reindex($class = null, $context = [])
    {
        $mappingConfig = $this->mapper->getMappingConfig();

        if (!$mappingConfig) {
            return;
        } elseif ($class) {
            if (!isset($mappingConfig[$class])) {
                throw new \InvalidArgumentException('There is no such entity in mapping config.');
            }
            $entitiesToIndex[$class] = $mappingConfig[$class];
        } else {
            $entitiesToIndex = $mappingConfig;
        }

        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY]) && $context[self::CONTEXT_WEBSITE_ID_KEY]) {
            $websiteIdsToIndex[] = $context[self::CONTEXT_WEBSITE_ID_KEY];
        } else {
            /** @var WebsiteRepository $websiteRepository */
            $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
            $websiteIdsToIndex = $websiteRepository->getAllWebsiteIds();
        }

        foreach ($websiteIdsToIndex as $websiteId) {
            $context[self::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
            $collectContextEvent = new CollectContextEvent($context);
            $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);
            $this->indexerContext = $collectContextEvent->getContext();

            foreach ($entitiesToIndex as $entityClassname => $entityConfig) {
                $this->reindexSingleEntity($entityClassname, $entityConfig, $context);
            }
        }
    }

    /**
     * Removes old index aliases and replace temporary aliases to real
     * @param string $realAlias
     * @param string $temporaryAlias
     */
    abstract protected function switchIndex($realAlias, $temporaryAlias);

    /**
     * @param string $entityClassname
     * @param array $entityConfig
     * @param array $context
     */
    protected function reindexSingleEntity($entityClassname, array $entityConfig, array $context)
    {
        $replaceTo = "website_{$context[self::CONTEXT_WEBSITE_ID_KEY]}";
        $entityAlias = str_replace('WEBSITE_ID', $replaceTo, $entityConfig['alias']);
        $entityAliasTemp = $entityAlias . '_' . time(); // TODO: Move aliases stuff to mapper provider

        $this->processedEntityAliases[$entityAlias] = $entityAliasTemp;

        $entityManager = $this->doctrineHelper->getEntityManagerForClass($entityClassname);
        $entityRepository = $entityManager->getRepository($entityClassname);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');

        $restrictEntitiesEvent = new RestrictIndexEntitiesEvent($queryBuilder, $entityClassname, $context);
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

            if (0 == $itemsCount % static::BATCH_SIZE) {
                $this->saveEntitiesBatch($entityIds, $entityClassname, $entityAliasTemp, $context);
                $entityManager->clear();
                $entityIds = [];
            }
        }

        if ($itemsCount % static::BATCH_SIZE > 0) {
            $this->saveEntitiesBatch($entityIds, $entityClassname, $entityAliasTemp, $context);
            $entityManager->clear();
        }
        $this->switchIndex($entityAlias, $entityAliasTemp);
    }

    /**
     * @param array $entityIds
     * @param string $entityClassname
     * @param string $entityAliasTemp
     * @param array $context
     */
    protected function saveEntitiesBatch(array $entityIds, $entityClassname, $entityAliasTemp, array $context)
    {
        $indexEntityEvent = new IndexEntityEvent($entityClassname, $entityIds, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);
        $indexedEntitiesBatch = $indexEntityEvent->getEntitiesData();
        $this->saveIndexData($indexedEntitiesBatch, $entityClassname, $entityAliasTemp, $context);
    }

    /**
     * Saves index data for batch of entities
     * @param array $indexedEntitiesBatch
     * @param string $entityClassname
     * @param string $entityAliasTemp
     * @param array $context
     */
    abstract protected function saveIndexData(
        array $indexedEntitiesBatch,
        $entityClassname,
        $entityAliasTemp,
        array $context
    );
}
