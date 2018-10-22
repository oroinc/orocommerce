<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverAwareTrait;

/**
 * Performs search indexation (save and delete) for ORM engine at website search index
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
            list($classes, $websiteIds) = $this->inputValidator->validateRequestParameters($entityClass, $context);
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
                ->saveItemData($indexData);
            $this->getDriver()->writeItem($item);
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
}
