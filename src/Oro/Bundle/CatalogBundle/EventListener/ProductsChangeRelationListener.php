<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductsChangeRelationListener
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection
                && $collection->getMapping()['fieldName'] === Category::FIELD_PRODUCTS
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                /** @var Product[] $productsChangedRelation */
                $productsChangedRelation = array_merge($collection->getInsertDiff(), $collection->getDeleteDiff());

                if ($productsChangedRelation) {
                    $event = new ProductsChangeRelationEvent($productsChangedRelation);
                    $this->eventDispatcher->dispatch(ProductsChangeRelationEvent::NAME, $event);
                }
            }
        }
    }
}
