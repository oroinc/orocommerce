<?php

namespace Oro\Bundle\ProductBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Clears a cache for product layout data provider
 * when a Segment entity is created, removed or changed.
 */
class SegmentEntityListener
{
    private CacheInterface $productCache;

    public function __construct(CacheInterface $productCache)
    {
        $this->productCache = $productCache;
    }

    public function preRemove(Segment $segment) : void
    {
        $this->productCache->clear();
    }

    public function postPersist(Segment $segment) : void
    {
        $this->productCache->clear();
    }

    public function preUpdate(Segment $segment, PreUpdateEventArgs $eventArgs) : void
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productCache->clear();
        }
    }
}
