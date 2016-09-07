<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

class OrmIndexer extends AbstractIndexer
{
    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entities, array $context = [])
    {
        $entities = $this->convertToArray($entities);

        if (empty($entities)) {
            return true;
        }

        $doctrineHelper = $this->doctrineHelper;
        usort($entities, function ($leftEntity, $rightEntity) use ($doctrineHelper) {
            return strcmp($doctrineHelper->getEntityClass($leftEntity), $doctrineHelper->getEntityClass($rightEntity));
        });

        while (!empty($entities)) {
            $entitiesBatch = $this->extractEntitiesBatch($entities);

            $this->removeEntitiesBatch($entitiesBatch, $context);
        }

        return true;
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getItemRepository()
    {
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(Item::class);

        return $entityManager->getRepository(Item::class);
    }

    /**
     * @param array $entities
     * @param array $context
     */
    private function removeEntitiesBatch(array $entities, array $context)
    {
        $entitiesClass = $this->doctrineHelper->getEntityClass(reset($entities));
        if (!$this->mappingProvider->isClassSupported($entitiesClass)) {
            return;
        }

        $entityAlias = null;
        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            $entityAlias = $this->mappingProvider->getEntityAlias($entitiesClass);
            $entityAlias = $this->applyPlaceholders($entityAlias, $context);
        }

        $entityIds = [];
        foreach ($entities as $entity) {
            $entityIds[] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        }

        $this->getItemRepository()->removeEntities($entityIds, $entitiesClass, $entityAlias);
    }

    /**
     * @param array $sortedEntities
     * @return array
     */
    private function extractEntitiesBatch(array &$sortedEntities)
    {
        $firstEntityClass = $this->doctrineHelper->getEntityClass(current($sortedEntities));
        $entitiesCount = 0;
        foreach ($sortedEntities as $entity) {
            if ($this->doctrineHelper->getEntityClass($entity) != $firstEntityClass) {
                break;
            }

            $entitiesCount++;
        }

        return array_splice($sortedEntities, 0, $entitiesCount);
    }


    /**
     * @param object|array $entities
     * @return array
     */
    private function convertToArray($entities)
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        // TODO: Implement getClassesForReindex() method.
    }

    /**
     * {@inheritdoc}
     */
    protected function saveIndexData(
        $entityClass,
        array $entitiesData,
        $entityAliasTemp
    ) {
        $em = $this->doctrineHelper->getEntityManager(Item::class);
        $items = [];
        foreach ($entitiesData as $entityId => $indexData) {
            $item = new Item();
            $item->setEntity($entityClass)
                ->setRecordId($entityId)
                ->setAlias($entityAliasTemp)
                ->setTitle($this->getEntityTitle($indexData))
                ->setChanged(false)
                ->saveItemData($indexData);
            $em->persist($item);
            $items[] = $item;
        }
        $em->flush($items);
        $em->clear(Item::class);

        return count($entitiesData);
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
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $itemRepository = $this->doctrineHelper->getEntityRepository(Item::class);
        $itemRepository->removeIndexByAlias($currentAlias);
        $itemRepository->renameIndexAlias($temporaryAlias, $currentAlias);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        // TODO: Implement resetIndex() method.
    }
}
