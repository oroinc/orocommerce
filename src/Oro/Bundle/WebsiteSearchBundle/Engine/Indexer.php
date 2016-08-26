<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

class Indexer extends AbstractIndexer
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
        $counter = 0;

        foreach ($entitiesData as $entityId => $indexData) {
            $item = new Item();
            $item->setEntity($entityClass)
                ->setRecordId($entityId)
                ->setAlias($entityAliasTemp)
                ->setTitle($this->getEntityTitle($indexData))
                ->setChanged(false)
                ->saveItemData($indexData);
            $em->persist($item);
            $counter++;
        }
        $em->flush();
        $em->clear();
        return $counter;
    }

    /**
     * Use first text field as a title
     * @param array $indexData
     * @return string
     */
    protected function getEntityTitle(array $indexData)
    {
        return isset($indexData[SearchQuery::TYPE_TEXT]) ? array_values($indexData[SearchQuery::TYPE_TEXT])[0] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $context = [])
    {
        // TODO: will be applied in BB-4083
    }

    /**
     * {@inheritdoc}
     */
    protected function renameIndex($oldAlias, $newAlias)
    {
        /** @var WebsiteSearchIndexRepository $itemRepository */
        $itemRepository = $this->doctrineHelper->getEntityRepository(Item::class);
        $itemRepository->removeIndexByAlias($newAlias);
        $itemRepository->renameTemporaryIndexAlias($oldAlias, $newAlias);
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        // TODO: will be applied in BB-4083
    }
}
