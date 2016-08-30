<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

class OrmIndexer extends AbstractIndexer
{
    /**
     * {@inheritdoc}
     */
    public function delete(array $entities, array $context = [])
    {
        if (empty($entities)) {
            return;
        }

        $firstEntityClass = $this->doctrineHelper->getEntityClass(current($entities));
        $entityIds = [];
        foreach ($entities as $entity) {
            if ($firstEntityClass !== $this->doctrineHelper->getEntityClass($entity)) {
                throw new \InvalidArgumentException('Entities must be of the same type');
            }

            $entityIds[] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        }

        $entityAlias = null;
        if (isset($context[self::CONTEXT_WEBSITE_ID_KEY])) {
            $websiteId = $context[self::CONTEXT_WEBSITE_ID_KEY];
            $entityAlias = $this->mapper->getEntityAlias($firstEntityClass);
            //TODO: replace with mapper or other service method call
            $entityAlias = str_replace('WEBSITE_ID', $websiteId, $entityAlias);
        }

        $entityManager = $this->doctrineHelper->getEntityManagerForClass(Item::class);

        /** @var WebsiteSearchIndexRepository $indexRepository */
        $indexRepository = $entityManager->getRepository(Item::class);
        $indexRepository->removeEntities($entityIds, $firstEntityClass, $entityAlias);
    }

    /**
     * {@inheritdoc}
     */
    public function renameIndex($oldAlias, $newAlias)
    {
        // TODO: Implement renameIndex() method.
    }

    /**
     * {@inheritdoc}
     */
    public function saveIndexData(
        $entityClass,
        array $entityIds,
        array $entitiesData,
        $entityAliasTemp,
        array $context
    ) {
        // TODO: Implement saveIndexData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, array $context = [])
    {
        // TODO: Implement resetIndex() method.
    }

    /**
     * {@inheritdoc}
     */
    public function save($entity, array $context = [])
    {
        // TODO: Implement save() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null, array $context = [])
    {
        // TODO: Implement getClassesForReindex() method.
    }
}
