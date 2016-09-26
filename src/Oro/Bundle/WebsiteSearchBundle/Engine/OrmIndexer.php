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
    public function delete($entities, array $context = [])
    {
        $entities = $this->convertToArray($entities);

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

            $this->getItemRepository()->removeEntities($entityIds, $entityClass, $entityAlias);
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
     * @param string $entityClass
     * @param array $context
     * @return string
     */
    private function getEntityAlias($entityClass, array $context)
    {
        $entityAlias = null;
        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            $entityAlias = $this->mappingProvider->getEntityAlias($entityClass);
            $entityAlias = $this->applyPlaceholders($entityAlias, $context);
        }

        return $entityAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        if (null === $class && !isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            $this->getItemRepository()->removeIndexByClass();

            return;
        }

        $entityClasses = [$class];
        if (null === $class) {
            $entityClasses = $this->mappingProvider->getEntityClasses();
        }

        foreach ($entityClasses as $entityClass) {
            $entityAlias = $this->getEntityAlias($entityClass, $context);

            if (null !== $entityAlias) {
                $this->getItemRepository()->removeIndexByAlias($entityAlias);
            } else {
                $this->getItemRepository()->removeIndexByClass($entityClass);
            }

        }
    }
}
