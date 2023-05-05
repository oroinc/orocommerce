<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterReindexEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolverInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract indexer for website search engine
 */
abstract class AbstractIndexer implements IndexerInterface
{
    use ContextTrait;

    const CONTEXT_ENTITY_CLASS_KEY = 'entityClass';
    const CONTEXT_ENTITIES_IDS_KEY = 'entityIds';
    const CONTEXT_WEBSITE_IDS = 'websiteIds';

    // generated automatically based on list of passed websites (see CONTEXT_WEBSITE_IDS)
    // must not be passed to indexer public methods outside via the context
    const CONTEXT_CURRENT_WEBSITE_ID_KEY = 'currentWebsiteId';

    // list of field groups that have to be reindexed, empty value means that all fields have to be reindexed
    const CONTEXT_FIELD_GROUPS = 'fieldGroups';

    /** @var EntityDependenciesResolverInterface */
    protected $entityDependenciesResolver;

    /** @var IndexDataProvider */
    protected $indexDataProvider;

    /** @var PlaceholderInterface */
    protected $placeholder;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SearchMappingProvider */
    protected $mappingProvider;

    /** @var IndexerInputValidator */
    protected $inputValidator;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @var PlaceholderInterface
     */
    protected $regexPlaceholder;

    /** @var int */
    private $batchSize = Configuration::INDEXER_BATCH_SIZE_DEFAULT;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        SearchMappingProvider $mappingProvider,
        EntityDependenciesResolverInterface $entityDependenciesResolver,
        IndexDataProvider $indexDataProvider,
        PlaceholderInterface $placeholder,
        IndexerInputValidator $indexerInputValidator,
        EventDispatcherInterface $eventDispatcher,
        PlaceholderInterface $regexPlaceholder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->entityDependenciesResolver = $entityDependenciesResolver;
        $this->indexDataProvider = $indexDataProvider;
        $this->placeholder = $placeholder;
        $this->inputValidator = $indexerInputValidator;
        $this->eventDispatcher = $eventDispatcher;
        $this->regexPlaceholder = $regexPlaceholder;
    }

    /**
     * Saves index data for batch of entities
     * @param string $entityClass
     * @param array $entitiesData
     * @param string $entityAliasTemp
     * @param array $context
     * @return array
     */
    abstract protected function saveIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    );

    /**
     * Saves partial index data for batch of entities
     * @param string $entityClass
     * @param array $entitiesData
     * @param string $entityAliasTemp
     * @param array $context
     * @return array
     */
    abstract protected function savePartialIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    );

    /**
     * Define which of the passed entities present in the website search index
     * @param string $entityClass
     * @param object[] $entities
     * @param array $context
     * @return object[]
     */
    abstract protected function getIndexedEntities($entityClass, array $entities, array $context);

    /**
     * Rename old index by aliases to new index
     * @param string $temporaryAlias
     * @param string $currentAlias
     * @throws \LogicException
     */
    abstract protected function renameIndex($temporaryAlias, $currentAlias);

    /**
     * {@inheritdoc}
     *
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     *     'websiteIds' int[] Array of websites ids to reindex
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     */
    public function reindex($classOrClasses = null, array $context = [])
    {
        [$entityClassesToIndex, $websiteIdsToIndex] =
            $this->inputValidator->validateRequestParameters(
                $classOrClasses,
                $context
            );

        $entityClassesToIndex = $this->getClassesForReindex($entityClassesToIndex);
        if (empty($context['skip_pre_processing'])) {
            $this->eventDispatcher->dispatch(
                new BeforeReindexEvent($classOrClasses, $context),
                BeforeReindexEvent::EVENT_NAME
            );
        }

        $handledItems = 0;

        foreach ($websiteIdsToIndex as $websiteId) {
            if (!$this->ensureWebsiteExists($websiteId)) {
                continue;
            }
            $websiteContext = $this->indexDataProvider->collectContextForWebsite($websiteId, $context);
            foreach ($entityClassesToIndex as $entityClass) {
                $handledItems += $this->reindexEntityClass($entityClass, $websiteContext);
            }
            //Check again to ensure Website was not deleted during reindexation otherwise drop index
            if (!$this->ensureWebsiteExists($websiteId)) {
                $handledItems = 0;
            }
        }

        return $handledItems;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context Not used here, only to comply with the interface
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        return $this->entityDependenciesResolver->getClassesForReindex($class);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context
     * $context = [
     *     'websiteIds' int[] Array of websites ids to index
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     */
    public function save($entityOrEntities, array $context = [])
    {
        $entities = is_array($entityOrEntities) ? $entityOrEntities : [$entityOrEntities];

        $entitiesByClass = [];
        foreach ($entities as $entity) {
            $entityClass = $this->doctrineHelper->getEntityClass($entity);
            $id = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $entitiesByClass[$entityClass][$id] = $id;
        }

        foreach ($entitiesByClass as $entityClass => $entityIds) {
            $context = $this->setContextEntityIds($context, $entityIds);
            $this->reindex($entityClass, $context);
        }

        return true;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param string $entityClass
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return int
     */
    protected function reindexEntityClass($entityClass, array $context)
    {
        $currentAlias = $this->getEntityAlias($entityClass, $context);
        $temporaryAlias = $currentAlias . '_' . uniqid('website_search', true);

        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);

        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
        $queryBuilder->select("entity.$identifierName as id");
        $contextEntityIds = $this->getContextEntityIds($context);

        if ($contextEntityIds) {
            $queryBuilder->where($queryBuilder->expr()->in("entity.$identifierName", ':contextEntityIds'))
                ->setParameter('contextEntityIds', array_values($contextEntityIds));
        }

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize($this->getBatchSize());

        $itemsCount = 0;
        $entityIds = [];
        $indexedContextEntityIds = [];
        $indexedItemsNum = 0;

        foreach ($iterator as $entity) {
            $entityIds[] = $entity['id'];
            $itemsCount++;
            if (0 === $itemsCount % $this->getBatchSize()) {
                $indexedEntityIds = $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
                $indexedItemsNum += count($indexedEntityIds);
                if ($contextEntityIds) {
                    $indexedContextEntityIds = array_merge($indexedContextEntityIds, $indexedEntityIds);
                }
                $entityIds = [];
                $entityManager->clear($entityClass);
            }
        }

        if ($itemsCount % $this->getBatchSize() > 0) {
            $indexedEntityIds = $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
            $indexedItemsNum += count($indexedEntityIds);
            if ($contextEntityIds) {
                $indexedContextEntityIds = array_merge($indexedContextEntityIds, $indexedEntityIds);
            }
            $entityManager->clear($entityClass);
        }

        if ($contextEntityIds) {
            $removedContextEntityIds = array_diff($contextEntityIds, $indexedContextEntityIds);
            if ($removedContextEntityIds) {
                $this->deleteEntities($entityClass, $removedContextEntityIds, $context);
            }
        } else {
            $this->renameIndex($temporaryAlias, $currentAlias);
        }

        $afterReindexEvent = new AfterReindexEvent(
            $entityClass,
            $context,
            $indexedContextEntityIds ?? $indexedEntityIds ?? [],
            $removedContextEntityIds ?? []
        );
        $this->eventDispatcher->dispatch($afterReindexEvent, AfterReindexEvent::EVENT_NAME);

        return $indexedItemsNum;
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @param array $context
     *
     * @return bool
     */
    protected function deleteEntities($entityClass, array $entityIds, array $context)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityClass);

        $entities = [];
        foreach ($entityIds as $id) {
            $entities[$id] = $entityManager->getReference($entityClass, $id);
        }

        // convert internal website ID format to external representation
        $websiteId = $this->getContextCurrentWebsiteId($context);
        if ($websiteId) {
            $context = $this->setContextWebsiteIds($context, [$websiteId]);
        }

        return $this->delete($entities, $context);
    }

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     *     'entityIds' int[] Array of entities ids to index
     * ]
     *
     * @param string $aliasToSave
     * @return array List of reindexed entity IDs
     */
    protected function indexEntities($entityClass, array $entityIds, array $context, $aliasToSave)
    {
        $restrictedEntities = $this->getRestrictedEntities($entityIds, $context, $entityClass);

        if (!$restrictedEntities) {
            return [];
        }

        $entityConfig = $this->mappingProvider->getEntityConfig($entityClass);

        // if partial reindexation is requested
        if (null !== $this->getContextFieldGroups($context)) {
            $indexedEntities = $this->getIndexedEntities($entityClass, $restrictedEntities, $context);
            $notIndexedEntities = array_diff_key($restrictedEntities, $indexedEntities);

            $result = [];

            // for presented entities only partial data can be collected
            if ($indexedEntities) {
                $existingEntityIds = array_keys($indexedEntities);
                $entitiesData = $this->indexDataProvider->getEntitiesData(
                    $entityClass,
                    $indexedEntities,
                    $context,
                    $entityConfig
                );
                $actualEntityIds = array_keys($entitiesData);
                // Fill entity data with empty data to be able to process removal of fields from the index for
                // existing entities when actual data has no such data anymore for a given field group.
                // Example: all product prices were removed for a product, and now prices must be removed from the index
                foreach (array_diff($existingEntityIds, $actualEntityIds) as $notPresentId) {
                    $entitiesData[$notPresentId] = [];
                }

                $this->savePartialIndexData($entityClass, $entitiesData, $aliasToSave, $context);
                // All indexed entities should be returned as entities present in the index to prevent documents removal
                // for cases when result set for fields group is empty (no images for product for example)
                $result = $existingEntityIds;
            }

            // for not presented entities all field groups have to be collected and indexed
            if ($notIndexedEntities) {
                $allFieldGroupsContext = $context;
                unset($allFieldGroupsContext[self::CONTEXT_FIELD_GROUPS]);

                $entitiesData = $this->indexDataProvider->getEntitiesData(
                    $entityClass,
                    $notIndexedEntities,
                    $allFieldGroupsContext,
                    $entityConfig
                );
                $result = array_merge(
                    $result,
                    $this->saveIndexData($entityClass, $entitiesData, $aliasToSave, $context)
                );
            }
        } else {
            $entitiesData = $this->indexDataProvider->getEntitiesData(
                $entityClass,
                $restrictedEntities,
                $context,
                $entityConfig
            );
            $result = $this->saveIndexData($entityClass, $entitiesData, $aliasToSave, $context);
        }

        return $result;
    }

    /**
     * @param array $entityIds
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @param string $entityClass
     * @return array
     */
    protected function getRestrictedEntities(array $entityIds, array $context, $entityClass)
    {
        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        $queryBuilder = $entityRepository->createQueryBuilder('entity');
        $queryBuilder = $this->indexDataProvider->getRestrictedEntitiesQueryBuilder(
            $entityClass,
            $queryBuilder,
            $context
        );
        $identifierName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);

        $queryBuilder
            ->select()
            ->andWhere($queryBuilder->expr()->in("entity.$identifierName", ':entityIds'))
            ->orderBy($queryBuilder->expr()->asc("entity.$identifierName"));

        $queryBuilder->setParameter('entityIds', $entityIds);

        $entities = $queryBuilder->getQuery()->getResult();

        $result = [];
        foreach ($entities as $entity) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            $result[$entityId] = $entity;
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     *
     * @return string|null
     */
    protected function getEntityAlias($entityClass, array $context)
    {
        if ($this->getContextCurrentWebsiteId($context)) {
            return $this->placeholder->replace(
                $this->mappingProvider->getEntityAlias($entityClass),
                [WebsiteIdPlaceholder::NAME => $this->getContextCurrentWebsiteId($context)]
            );
        }

        return null;
    }

    /**
     * @param $websiteId
     * @return bool
     */
    protected function ensureWebsiteExists($websiteId)
    {
        /** @var WebsiteRepository $websiteRepository */
        $websiteRepository = $this->doctrineHelper->getEntityRepository(Website::class);
        $website           = $websiteRepository->checkWebsiteExists($websiteId);

        //Tries to reset index for not existing website
        if (!$website) {
            $context = $this->setContextWebsiteIds([], [$websiteId]);
            $this->resetIndex(null, $context);

            return false;
        }

        return true;
    }

    protected function getFieldsForGroup(string $entityClass, array $context): array
    {
        $fieldGroups = $this->getContextFieldGroups($context);
        $fields = $this->mappingProvider->getMappingConfig()[$entityClass]['fields'] ?? [];

        return array_filter(
            $fields,
            static function (array $field) use ($fieldGroups) {
                return !empty($field['group']) && in_array($field['group'], $fieldGroups, true);
            }
        );
    }
}
