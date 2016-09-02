<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

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

        if (empty($entities)) {
            return true;
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
            $entityAlias = $this->mapper->getEntityAlias($firstEntityClass);
            $entityAlias = $this->applyPlaceholders($entityAlias, $context);
        }

        $entityManager = $this->doctrineHelper->getEntityManagerForClass(Item::class);

        /** @var WebsiteSearchIndexRepository $indexRepository */
        $indexRepository = $entityManager->getRepository(Item::class);
        $indexRepository->removeEntities($entityIds, $firstEntityClass, $entityAlias);

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
