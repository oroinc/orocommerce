<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

class ReindexRequestListener
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var IndexerInterface|null
     */
    protected $regularIndexer;

    /**
     * @var IndexerInterface|null
     */
    protected $asyncIndexer;

    /**
     * @param EntityManager         $entityManager
     * @param IndexerInterface|null $regularIndexer
     * @param IndexerInterface|null $asyncIndexer
     */
    public function __construct(
        EntityManager    $entityManager,
        IndexerInterface $regularIndexer = null,
        IndexerInterface $asyncIndexer = null
    ) {
        $this->entityManager  = $entityManager;
        $this->regularIndexer = $regularIndexer;
        $this->asyncIndexer   = $asyncIndexer;
    }

    /**
     * @param ReindexationTriggerEvent $event
     */
    public function process(ReindexationTriggerEvent $event)
    {
        $indexer = $event->isScheduled() ? $this->asyncIndexer : $this->regularIndexer;
        if ($indexer !== null) {
            $this->processWithIndexer($event, $indexer);
        }
    }

    /**
     * @param ReindexationTriggerEvent $event
     * @param IndexerInterface $indexer
     */
    protected function processWithIndexer(ReindexationTriggerEvent $event, IndexerInterface $indexer)
    {
        $className = $event->getClassName();
        $ids = $event->getIds();
        $context = $this->buildContext($event);

        if (empty($ids)) {
            $indexer->reindex($className, $context);
        } elseif (empty($className)) {
            throw new \LogicException('Event data cannot have IDs without class name');
        } else {
            list($savedEntities, $deletedEntities) = $this->getSavedAndDeletedEntities($event);

            $indexer->save($savedEntities, $context);

            $indexer->delete($deletedEntities, $context);
        }
    }

    /**
     * @param ReindexationTriggerEvent $event
     *
     * @return array [ [0] => <entities to save>, [1] => <entities to delete> ]
     *
     */
    protected function getSavedAndDeletedEntities(ReindexationTriggerEvent $event)
    {
        $class = $event->getClassName();
        $repository = $this->entityManager->getRepository($class);
        $metadata = $this->entityManager->getClassMetadata($class);
        $idColumn = $metadata->getSingleIdentifierFieldName();
        $queryBuilder = $repository->createQueryBuilder('e');

        $savedEntities = $queryBuilder->andWhere($queryBuilder->expr()->in('e.' . $idColumn, $event->getIds()))
            ->getQuery()
            ->getResult();

        $deletedIds = $event->getIds();

        foreach ($savedEntities as $savedEntity) {
            $ids = $metadata->getIdentifierValues($savedEntity);
            $id =  current($ids);

            $deletedIdIndex = array_search($id, $deletedIds, true);
            if ($deletedIdIndex !== false) {
                unset($deletedIds[$deletedIdIndex]);
            }
        }

        $deletedEntities = [];
        foreach ($deletedIds as $id) {
            $deletedEntities[] = $this->entityManager->getReference($class, $id);
        }

        return [$savedEntities, $deletedEntities];
    }

    /**
     * @param ReindexationTriggerEvent $event
     * @return array
     */
    protected function buildContext(ReindexationTriggerEvent $event)
    {
        $context = [];

        $websiteId = $event->getWebsiteId();
        if (!empty($websiteId)) {
            // TODO uncomment when AbstractIndexer is available
            // $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
        }

        return $context;
    }
}
