<?php

namespace Oro\Bundle\ProductBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;

class SegmentEntityListener
{
    /** @var DataProviderCacheCleaner */
    private $productCacheCleaner;

    /**
     * @param DataProviderCacheCleaner $cacheCleaner
     */
    public function __construct(DataProviderCacheCleaner $cacheCleaner)
    {
        $this->productCacheCleaner = $cacheCleaner;
    }

    /**
     * @param Segment $segment
     */
    public function preRemove(Segment $segment)
    {
        $this->productCacheCleaner->clearCache();
    }

    /**
     * @param Segment $segment
     */
    public function postPersist(Segment $segment)
    {
        $this->productCacheCleaner->clearCache();
    }

    /**
     * @param Segment $segment
     * @param PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(Segment $segment, PreUpdateEventArgs $eventArgs)
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->productCacheCleaner->clearCache();
        }
    }
}
