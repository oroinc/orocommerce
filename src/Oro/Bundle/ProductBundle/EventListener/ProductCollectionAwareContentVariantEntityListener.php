<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * This listener is used to track segment entity's creation, remove
 * and definition change to schedule it for re-indexation
 */
class ProductCollectionAwareContentVariantEntityListener
{
    /**
     * @var ProductCollectionVariantReindexMessageSendListener
     */
    private $reindexEventListener;

    /**
     * @param ProductCollectionVariantReindexMessageSendListener $reindexEventListener
     */
    public function __construct(
        ProductCollectionVariantReindexMessageSendListener $reindexEventListener
    ) {
        $this->reindexEventListener = $reindexEventListener;
    }

    /**
     * @param Segment $segment
     */
    public function postPersist(Segment $segment)
    {
        $this->reindexEventListener->scheduleSegment($segment);
    }

    /**
     * @param Segment $segment
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Segment $segment, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('definition')) {
            $this->reindexEventListener->scheduleSegment($segment);
        }
    }

    /**
     * @param Segment $segment
     */
    public function preRemove(Segment $segment)
    {
        $this->reindexEventListener->scheduleMessageBySegmentDefinition($segment);
    }
}
