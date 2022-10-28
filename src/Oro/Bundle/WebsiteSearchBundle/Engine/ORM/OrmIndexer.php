<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Entity\ItemFieldInterface;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverAwareTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

/**
 * Performs search indexation (save and delete) for ORM engine at website search index
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrmIndexer extends AbstractIndexer
{
    use DriverAwareTrait;
    use ContextTrait;

    /**
     * {@inheritdoc}
     */
    public function delete($entities, array $context = [])
    {
        $entities = is_array($entities) ? $entities : [$entities];

        $sortedEntitiesData = [];
        foreach ($entities as $entity) {
            if (!$this->doctrineHelper->isManageableEntity($entity)) {
                continue;
            }

            $entityClass = $this->doctrineHelper->getEntityClass($entity);

            if ($this->mappingProvider->isClassSupported($entityClass)) {
                $sortedEntitiesData[$entityClass][] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            }
        }

        foreach ($sortedEntitiesData as $entityClass => $entityIds) {
            // $classes can be skipped, because current class was already validated at filterEntityData
            [$classes, $websiteIds] = $this->inputValidator->validateRequestParameters($entityClass, $context);
            foreach ($websiteIds as $websiteId) {
                $websiteContext = $this->setContextCurrentWebsite([], $websiteId);
                $entityAlias = $this->getEntityAlias($entityClass, $websiteContext);
                $batches = array_chunk($entityIds, $this->getBatchSize());
                foreach ($batches as $batch) {
                    $this->getDriver()->removeEntities($batch, $entityClass, $entityAlias);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to index
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     */
    protected function saveIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    ) {
        $entityIds = array_keys($entitiesData);

        // Save entities directly with real alias if entity ids passed to context
        if ($this->getContextEntityIds($context)) {
            $entityAliasTemp = $this->getEntityAlias($entityClass, $context);
        }

        // Build items for search index
        foreach ($entitiesData as $entityId => $indexData) {
            $this->createAndWriteNewItem($entityClass, $entityId, $entityAliasTemp, $indexData);
        }

        // Remove old data to prevent possible conflicts with unique indexes
        $this->deleteEntities($entityClass, $entityIds, $context);

        // Insert data to the database
        $this->getDriver()->flushWrites();

        return $entityIds;
    }

    /**
     * Use first text field as a title
     * @param array $indexData
     * @return string
     */
    protected function getEntityTitle(array $indexData)
    {
        return isset($indexData[SearchQuery::TYPE_TEXT]) ? reset($indexData[SearchQuery::TYPE_TEXT]) : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function renameIndex($temporaryAlias, $currentAlias)
    {
        $this->getDriver()->removeIndexByAlias($currentAlias);
        $this->getDriver()->renameIndexAlias($temporaryAlias, $currentAlias);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        $websiteIds = $this->getContextWebsiteIds($context);

        //Resets index for class or passed website IDs
        if ($class || $websiteIds) {
            $entityClasses = $class ? (array)$class : $this->mappingProvider->getEntityClasses();

            foreach ($entityClasses as $entityClass) {
                if ($websiteIds) {
                    foreach ($websiteIds as $websiteId) {
                        $websiteContext = $this->setContextCurrentWebsite([], $websiteId);
                        $entityAlias = $this->getEntityAlias($entityClass, $websiteContext);
                        $this->getDriver()->removeIndexByAlias($entityAlias);
                    }
                } else {
                    $this->getDriver()->removeIndexByClass($entityClass);
                }
            }
        } else { //Resets whole index
            $this->getDriver()->removeIndexByClass();
        }
    }

    protected function savePartialIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    ) {
        $realAlias = $this->getEntityAlias($entityClass, $context);

        if (null === $realAlias) {
            return [];
        }

        // need to get data from index to index full document later
        $items = $this->loadItems($entityClass, array_keys($entitiesData), $context);

        $existingDocuments = [];
        foreach ($items as $item) {
            $existingDocuments[$item->getRecordId()] = $item;
        }

        // Save entities directly with real alias if entity ids passed to context
        if ($this->getContextEntityIds($context)) {
            $entityAliasTemp = $this->getEntityAlias($entityClass, $context);
        }

        $entityIds = array_keys($entitiesData);
        foreach ($entitiesData as $entityId => $entityData) {
            // if entity was removed there is no need to do anything
            if (!array_key_exists($entityId, $existingDocuments)) {
                unset($entitiesData[$entityId]);
                continue;
            }

            $this->processFieldsCollection($existingDocuments[$entityId], $entityData);

            $this->createAndWriteNewItem($entityClass, $entityId, $entityAliasTemp, $entityData);
        }
        // Remove old data to prevent possible conflicts with unique indexes
        $this->deleteEntities($entityClass, $entityIds, $context);

        // Insert data to the database
        $this->getDriver()->flushWrites();

        return $entityIds;
    }

    private function processFieldsCollection(Item $item, array &$entityData): void
    {
        /** @var Collection|ItemFieldInterface[] $collection */
        foreach ($item->getAllFields() as $fieldType => $collection) {
            foreach ($collection as $fieldItem) {
                $name = $fieldItem->getField();

                // Skip update of fields if data is already in the index
                if (isset($entityData[$fieldType][$name])
                    && $entityData[$fieldType][$name] === $fieldItem->getValue()) {
                    continue;
                }

                // Add fields from other groups back to $entityData to prevent their removal
                if (!array_key_exists($fieldType, $entityData)) {
                    $entityData[$fieldType] = [];
                }
                if (!array_key_exists($name, $entityData[$fieldType])) {
                    $entityData[$fieldType][$name] = $fieldItem->getValue();
                }
            }
        }
    }

    protected function getIndexedEntities($entityClass, array $entities, array $context)
    {
        $recordIds = $this->getIndexedRecordIds($entityClass, array_keys($entities), $context);

        $indexedEntities = [];
        foreach ($recordIds as $entityId) {
            if (array_key_exists($entityId, $entities)) {
                $indexedEntities[$entityId] = $entities[$entityId];
            }
        }

        return $indexedEntities;
    }

    private function getIndexedRecordIds(string $entityClass, array $entityIds, array $context): array
    {
        $qb = $this->getItemsQueryBuilder($entityClass, $entityIds, $context);
        $qb->select('i.recordId');

        return $qb->getQuery()->getSingleColumnResult();
    }

    /**
     * @return AbstractItem[]
     */
    private function loadItems(string $entityClass, array $entityIds, array $context): array
    {
        $qb = $this->getItemsQueryBuilder($entityClass, $entityIds, $context);

        return $qb->getQuery()->getResult();
    }

    private function getItemsQueryBuilder(
        string $entityClass,
        array $entityIds,
        array $context
    ): QueryBuilder {
        $entityAlias = $this->getEntityAlias($entityClass, $context);
        $qb = $this->getDriver()->createQueryBuilder('i');
        $qb
            ->where($qb->expr()->eq('i.alias', ':alias'))
            ->andWhere($qb->expr()->in('i.recordId', ':ids'))
            ->setParameter('alias', $entityAlias)
            ->setParameter('ids', $entityIds);

        return $qb;
    }

    private function createAndWriteNewItem($entityClass, $entityId, $entityAliasTemp, $data)
    {
        $item = $this->getDriver()->createItem();

        if (isset($indexData[SearchQuery::TYPE_DECIMAL][self::WEIGHT_FIELD])) {
            $item->setWeight($indexData[SearchQuery::TYPE_DECIMAL][self::WEIGHT_FIELD]);
            unset($indexData[SearchQuery::TYPE_DECIMAL][self::WEIGHT_FIELD]);
        }

        $item->setEntity($entityClass)
            ->setRecordId($entityId)
            ->setAlias($entityAliasTemp)
            ->setTitle($this->getEntityTitle($indexData))
            ->setChanged(false)
            ->saveItemData($data);

        $this->getDriver()->writeItem($item);
    }
}
