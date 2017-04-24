<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This listener is used to send message into queue if definition of segment attached to content variant changes.
 */
class ContentVariantSegmentListener
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var SegmentMessageFactory
     */
    private $messageFactory;

    /**
     * @var ProductCollectionSegmentHelper
     */
    private $productCollectionSegmentHelper;

    /**
     * @var Segment[]
     */
    private $segments = [];

    /**
     * @param MessageProducerInterface $messageProducer
     * @param SegmentMessageFactory $messageFactory
     * @param ProductCollectionSegmentHelper $productCollectionSegmentHelper
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        SegmentMessageFactory $messageFactory,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper
    ) {
        $this->messageProducer = $messageProducer;
        $this->messageFactory = $messageFactory;
        $this->productCollectionSegmentHelper = $productCollectionSegmentHelper;
    }

    public function postFlush()
    {
        while ($segment = array_pop($this->segments)) {
            $this->createMessage($segment);
        }
    }

    /**
     * @param Segment $segment
     */
    public function scheduleSegment(Segment $segment)
    {
        $this->segments[$segment->getId()] = $segment;
    }

    /**
     * @param Segment $segment
     */
    private function createMessage(Segment $segment)
    {
        $websiteIds = $this->productCollectionSegmentHelper->getWebsiteIdsBySegment($segment);

        if (!empty($websiteIds)) {
            $this->messageProducer->send(
                Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                $this->messageFactory->createMessage($segment, $websiteIds)
            );
        }
    }
}
