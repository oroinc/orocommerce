<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverAwareTrait;

class OrmIndexer extends AbstractIndexer
{
    use DriverAwareTrait;
    use ContextTrait;

    /**
     * {@inheritdoc}
     *
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
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
            $entityAlias = $this->getEntityAlias($entityClass, $context);
            $batches = array_chunk($entityIds, $this->getBatchSize());
            foreach ($batches as $batch) {
                $this->getDriver()->removeEntities($batch, $entityClass, $entityAlias);
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
        $items = [];
        foreach ($entitiesData as $entityId => $indexData) {
            $item = $this->getDriver()->createItem();
            $item->setEntity($entityClass)
                ->setRecordId($entityId)
                ->setAlias($entityAliasTemp)
                ->setTitle($this->getEntityTitle($indexData))
                ->setChanged(false)
                ->saveItemData($indexData);
            $this->getDriver()->writeItem($item);
            $items[] = $item;
        }

        // Remove old data to prevent possible conflicts with unique indexes
        $this->deleteEntities($entityClass, $entityIds, $context);

        // Insert data to the database
        $this->getDriver()->flushWrites();

        return count($items);
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
     *
     * @param array $context
     * $context = [
     *     'currentWebsiteId' int Current website id. Should not be passed manually. It is computed from 'websiteIds'
     * ]
     */
    public function resetIndex($class = null, array $context = [])
    {
        $currentWebsiteId = $this->getContextCurrentWebsiteId($context);

        //Resets index for class or CurrentWebsite if passed to context
        if ($class || $currentWebsiteId) {
            $entityClasses = $class ? [$class] : $this->mappingProvider->getEntityClasses();

            foreach ($entityClasses as $entityClass) {
                if ($currentWebsiteId) {
                    $entityAlias = $this->getEntityAlias($entityClass, $context);
                    $this->getDriver()->removeIndexByAlias($entityAlias);
                } else {
                    $this->getDriver()->removeIndexByClass($entityClass);
                }
            }
        } //Resets whole index
        else {
            $this->getDriver()->removeIndexByClass();
        }
    }
}
