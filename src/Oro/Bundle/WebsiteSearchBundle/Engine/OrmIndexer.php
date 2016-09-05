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

        $entityManager = $this->doctrineHelper->getEntityManagerForClass(Item::class);

        /** @var WebsiteSearchIndexRepository $indexRepository */
        $indexRepository = $entityManager->getRepository(Item::class);

        while (!empty($entities)) {
            $firstEntityClass = $doctrineHelper->getEntityClass(current($entities));
            $sameEntitiesCount = 0;
            foreach ($entities as $entity) {
                if ($doctrineHelper->getEntityClass($entity) != $firstEntityClass) {
                    break;
                }

                $sameEntitiesCount++;
            }

            $sameEntities = array_splice($entities, 0, $sameEntitiesCount);
            $entityAlias = null;
            if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
                $entityAlias = $this->mappingProvider->getEntityAlias($firstEntityClass);
                if (null === $entityAlias) {
                    continue;
                }

                $entityAlias = $this->applyPlaceholders($entityAlias, $context);
            }

            $entityIds = [];
            foreach ($sameEntities as $entity) {
                $entityIds[] = $doctrineHelper->getSingleEntityIdentifier($entity);
            }

            $indexRepository->removeEntities($entityIds, $firstEntityClass, $entityAlias);
        }

        return true;
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
