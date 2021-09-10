<?php

namespace Oro\Bundle\ProductBundle\Entity\EntityListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Clears a cache for product layout data provider
 * when a Segment entity is created, removed or changed.
 */
class SegmentEntityListener
{
    /** @var CacheProvider */
    private $productCache;

    public function __construct(CacheProvider $productCache)
    {
        $this->productCache = $productCache;
    }

    public function preRemove(Segment $segment)
    {
        $this->productCache->deleteAll();
    }

    public function postPersist(Segment $segment)
    {
        $this->productCache->deleteAll();
    }

    public function preUpdate(Segment $segment, PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productCache->deleteAll();
        }
    }
}
