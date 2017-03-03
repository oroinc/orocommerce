<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolverInterface;

abstract class AbstractIndexer implements IndexerInterface
{
    use ContextTrait;

    const CONTEXT_CURRENT_WEBSITE_ID_KEY = 'currentWebsiteId';
    const CONTEXT_ENTITIES_IDS_KEY = 'entityIds';
    const CONTEXT_WEBSITE_IDS = 'websiteIds';

    /** @var EntityDependenciesResolverInterface */
    protected $entityDependenciesResolver;

    /** @var IndexDataProvider */
    protected $indexDataProvider;

    /** @var PlaceholderInterface */
    protected $placeholder;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /** @var IndexerInputValidator */
    protected $inputValidator;

    /** @var int */
    private $batchSize = 100;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     * @param EntityDependenciesResolverInterface $entityDependenciesResolver
     * @param IndexDataProvider $indexDataProvider
     * @param PlaceholderInterface $placeholder
     * @param IndexerInputValidator $indexerInputValidator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider,
        EntityDependenciesResolverInterface $entityDependenciesResolver,
        IndexDataProvider $indexDataProvider,
        PlaceholderInterface $placeholder,
        IndexerInputValidator $indexerInputValidator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->entityDependenciesResolver = $entityDependenciesResolver;
        $this->indexDataProvider = $indexDataProvider;
        $this->placeholder = $placeholder;
        $this->inputValidator = $indexerInputValidator;
    }

    /**
     * Saves index data for batch of entities
     * @param string $entityClass
     * @param array $entitiesData
     * @param string $entityAliasTemp
     * @param array $context
     * @return int
     */
    abstract protected function saveIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    );

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
        list($entityClassesToIndex, $websiteIdsToIndex) =
            $this->inputValidator->validateReindexRequest(
                $classOrClasses,
                $context
            );

        $entityClassesToIndex = $this->getClassesForReindex($entityClassesToIndex);

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
            //Remove certain entities from index before reindexation
            $entities = [];
            foreach ($contextEntityIds as $id) {
                $entities[$id] = $entityManager->getReference($entityClass, $id);
            }
            $this->delete($entities, $context);

            $queryBuilder->where($queryBuilder->expr()->in("entity.$identifierName", $contextEntityIds));
        }

        $iterator = new BufferedIdentityQueryResultIterator($queryBuilder);
        $iterator->setBufferSize($this->getBatchSize());

        $itemsCount = 0;
        $entityIds = [];
        $indexedItemsNum = 0;

        foreach ($iterator as $entity) {
            $entityIds[] = $entity['id'];
            $itemsCount++;
            if (0 === $itemsCount % $this->getBatchSize()) {
                $indexedItemsNum += $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
                $entityIds = [];
                $entityManager->clear($entityClass);
            }
        }

        if ($itemsCount % $this->getBatchSize() > 0) {
            $indexedItemsNum += $this->indexEntities($entityClass, $entityIds, $context, $temporaryAlias);
            $entityManager->clear($entityClass);
        }

        if (!$contextEntityIds) {
            $this->renameIndex($temporaryAlias, $currentAlias);
        }

        return $indexedItemsNum;
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
     * @return int
     */
    protected function indexEntities($entityClass, array $entityIds, array $context, $aliasToSave)
    {
        $restrictedEntities = $this->getRestrictedEntities($entityIds, $context, $entityClass);

        if (!$restrictedEntities) {
            return 0;
        }

        $entityConfig = $this->mappingProvider->getEntityConfig($entityClass);
        $entitiesData = $this->indexDataProvider->getEntitiesData(
            $entityClass,
            $restrictedEntities,
            $context,
            $entityConfig
        );

        return $this->saveIndexData($entityClass, $entitiesData, $aliasToSave, $context);
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

        return $queryBuilder->getQuery()->getResult();
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
            $context = $this->setContextCurrentWebsite([], $websiteId);
            $this->resetIndex(null, $context);

            return false;
        }

        return true;
    }
}
