<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ProductCollectionVariantReindexMessageSendListener
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
     * @var array
     */
    private $scheduledMessages = [];

    /**
     * @var Segment[]
     */
    private $segments = [];

    /**
     * @param MessageProducerInterface $messageProducer
     * @param ProductCollectionSegmentHelper $productCollectionSegmentHelper
     * @param SegmentMessageFactory $messageFactory
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper,
        SegmentMessageFactory $messageFactory
    ) {
        $this->messageProducer = $messageProducer;
        $this->productCollectionSegmentHelper = $productCollectionSegmentHelper;
        $this->messageFactory = $messageFactory;
    }

    public function postFlush()
    {
        $this->addSegmentsMessages();
        while ($message = array_pop($this->scheduledMessages)) {
            $this->messageProducer->send(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $message);
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
    public function scheduleMessageBySegmentDefinition(Segment $segment)
    {
        $websiteIds = $this->productCollectionSegmentHelper->getWebsiteIdsBySegment($segment);

        if (count($websiteIds) > 0) {
            $message = $this->messageFactory->createMessage($websiteIds, null, $segment->getDefinition());
            $this->scheduledMessages[$this->getMessageKey($message)] = $message;
        }
    }

    private function addSegmentsMessages()
    {
        while ($segment = array_pop($this->segments)) {
            $websiteIds = $this->productCollectionSegmentHelper->getWebsiteIdsBySegment($segment);

            if (count($websiteIds) > 0) {
                $message = $this->messageFactory->createMessage($websiteIds, $segment);
                $this->scheduledMessages[$this->getMessageKey($message)] = $message;
            }
        }
    }

    /**
     * @param array $message
     * @return string
     */
    private function getMessageKey(array $message): string
    {
        return md5(json_encode($message));
    }
}
