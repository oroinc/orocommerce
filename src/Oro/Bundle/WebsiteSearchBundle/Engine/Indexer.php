<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

class Indexer extends AbstractIndexer
{
    /**
     * {@inheritdoc}
     */
    public function save($entity, $context = [])
    {
        // TODO: Implement save() method.
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
    public function getClassesForReindex($class = null, $context = [])
    {
        // TODO: Implement getClassesForReindex() method.
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, $context = [])
    {
        // TODO: Implement delete() method.
    }

    /**
     * {@inheritdoc}
     */
    public function renameIndex($oldAlias, $newAlias)
    {
        $em = $this->doctrineHelper->getEntityManager(Item::class);
        $itemRepository = $em->getRepository(Item::class);
        $qb = $itemRepository->createQueryBuilder('item');
        $qb->delete()
            ->where($qb->expr()->eq('item.alias', $newAlias))
            ->getQuery()
            ->execute();

        $qb->update()->set('item.alias', $newAlias)
            ->where($qb->expr()->eq('item.alias', $oldAlias))
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, $context = [])
    {
        // TODO: Implement resetIndex() method.
    }
}
