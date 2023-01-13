<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Watch changes of product sort orders in collections and trigger update of search index
 */
class ProductCollectionSortOrderListener
{
    protected array $changedCollectionSortOrder = [];

    public function __construct(
        protected RequestStack $requestStack,
        protected EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * Collects distinct product ids with changed CollectionSortOrder value in any Segment
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (!$this->requestStack->getMainRequest()) {
            return;
        }

        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        // Getting all changed CollectionSortOrder
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->filterChanges($entity);
        }
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->filterChanges($entity);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->filterChanges($entity);
        }
    }

    /**
     * Trigger search indexation for Products pre-selected in onFlush event only for collection_sort_order index group
     */
    public function postFlush(): void
    {
        if (!$this->changedCollectionSortOrder) {
            return;
        }

        $this->dispatcher->dispatch(
            new ReindexationRequestEvent(
                [Product::class],
                [],
                $this->changedCollectionSortOrder,
                true,
                ['collection_sort_order']
            ),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    public function onClear(OnClearEventArgs $event): void
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === CollectionSortOrder::class) {
            $this->changedCollectionSortOrder = [];
        }
    }

    /**
     * @param $entity
     * @return void
     */
    private function filterChanges($entity): void
    {
        if ($entity instanceof CollectionSortOrder) {
            if (!in_array($entity->getProduct()->getId(), $this->changedCollectionSortOrder)) {
                $this->changedCollectionSortOrder[] = $entity->getProduct()->getId();
            }
        }
    }
}
