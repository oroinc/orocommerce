<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
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

    const CONTEXT_CURRENT_WEBSITE_ID_KEY = 'current_website_id';
    const CONTEXT_ENTITIES_IDS_KEY = 'entityIds';
    const CONTEXT_WEBSITE_IDS = 'websiteIds';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /** @var EntityDependenciesResolverInterface */
    protected $entityDependenciesResolver;

    /** @var IndexDataProvider */
    protected $indexDataProvider;

    /** @var PlaceholderInterface */
    protected $placeholder;

    /** @var int */
    private $batchSize = 100;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     * @param EntityDependenciesResolverInterface $entityDependenciesResolver
     * @param IndexDataProvider $indexDataProvider
     * @param PlaceholderInterface $placeholder
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider,
        EntityDependenciesResolverInterface $entityDependenciesResolver,
        IndexDataProvider $indexDataProvider,
        PlaceholderInterface $placeholder
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->entityDependenciesResolver = $entityDependenciesResolver;
        $this->indexDataProvider = $indexDataProvider;
        $this->placeholder = $placeholder;
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
     */
    public function reindex($classOrClasses = null, array $context = [])
    {
        if (is_array($classOrClasses) && count($classOrClasses) !== 1 && $this->getContextEntityIds($context)) {
            throw new \LogicException('Entity ids passed into context. Please provide single class of entity');
        }

        $entityClassesToIndex = $this->getEntitiesToIndex($classOrClasses);
        $websiteIdsToIndex = $this->getWebsiteIdsToIndex($context);
        $handledItems = 0;

        $entityClassesToIndex = $this->getClassesForReindex($entityClassesToIndex);

        foreach ($websiteIdsToIndex as $websiteId) {
            $websiteContext = $this->indexDataProvider->collectContextForWebsite($websiteId, $context);
            foreach ($entityClassesToIndex as $entityClass) {
                $handledItems += $this->reindexEntityClass($entityClass, $websiteContext);
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
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        return $this->entityDependenciesResolver->getClassesForReindex($class);
    }

    /**
     * @param string $class
     * @return array
     */
    private function getEntitiesToIndex($class = null)
    {
        $entityClasses = (array)$class;
        if ($entityClasses) {
            foreach ($entityClasses as $entityClass) {
                $this->ensureEntityClassIsSupported($entityClass);
            }
        } else {
            $entityClasses = $this->mappingProvider->getEntityClasses();
        }

        return $entityClasses;
    }

    /**
     * {@inheritdoc}
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
     * @param array $context
     * @return array
     */
    protected function getWebsiteIdsToIndex(array $context)
    {
        $websiteIds = $this->getContextWebsiteIds($context);
        if ($websiteIds) {
            return $websiteIds;
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
            $temporaryAlias = $currentAlias; //Save context entities with real alias
        }

        $iterator = new BufferedQueryResultIterator($queryBuilder);
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
     * @param array $entityIds
     * @param array $context
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
     * @return string
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
}
