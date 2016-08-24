<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

class OrmIndexer extends AbstractIndexer
{
    /**
     * @var WebsiteSearchIndexRepository
     */
    private $indexRepository;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param Mapper $mapper
     * @param WebsiteSearchIndexRepository $indexRepository
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        Mapper $mapper,
        WebsiteSearchIndexRepository $indexRepository
    ) {
        parent::__construct($eventDispatcher, $doctrineHelper, $mapper);

        $this->indexRepository = $indexRepository;
    }

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
        if (isset($context['website_id'])) {
            $websiteId = $context['website_id'];
            $entityAlias = $this->mapper->getEntityAlias($firstEntityClass);
            $entityAlias = str_replace('WEBSITE_ID', $websiteId, $entityAlias);
        }

        $this->indexRepository->removeItemEntities($entityIds, $firstEntityClass, $entityAlias);
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
    )
    {
        // TODO: Implement saveIndexData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function resetIndex($class = null, $context = [])
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
    public function getClassesForReindex($class = null, $context = [])
    {
        // TODO: Implement getClassesForReindex() method.
    }
}
