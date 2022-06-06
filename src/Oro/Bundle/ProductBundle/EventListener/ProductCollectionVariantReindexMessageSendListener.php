<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Async\Topic\AccumulateReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandlerInterface as ReindexHandler;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandlerWithFieldGroupsInterface
    as ReindexHandlerWithFieldGroups;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\AccumulateSegmentMessageFactory as AccumulateMessageFactory;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Listener that sends product segment data to reindex.
 */
class ProductCollectionVariantReindexMessageSendListener
{
    const SEGMENT = 'segment';
    const IS_FULL = 'is_full';
    const ADDITIONAL_PRODUCTS = 'additional_products';

    private ReindexHandler $collectionIndexationHandler;
    private AccumulateMessageFactory $accumulateMessageFactory;

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
     * @var array
     */
    private $segments = [];

    public function __construct(
        MessageProducerInterface $messageProducer,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper,
        SegmentMessageFactory $messageFactory
    ) {
        $this->messageProducer = $messageProducer;
        $this->productCollectionSegmentHelper = $productCollectionSegmentHelper;
        $this->messageFactory = $messageFactory;
    }

    public function setReindexHandler(ReindexHandler $collectionIndexationHandler)
    {
        $this->collectionIndexationHandler = $collectionIndexationHandler;
    }

    public function setAccumulateMessageFactory(AccumulateMessageFactory $accumulateMessageFactory)
    {
        $this->accumulateMessageFactory = $accumulateMessageFactory;
    }

    public function postFlush(): void
    {
        $this->addSegmentsMessages();
        if (empty($this->scheduledPartialMessages)) {
            return;
        }

        $scheduledPartialMessages = $this->scheduledPartialMessages;
        $this->scheduledPartialMessages = [];
        $rootJobName = sprintf(
            '%s:%s:%s',
            AccumulateReindexProductCollectionBySegmentTopic::NAME,
            'listener',
            $this->getMessageKey($scheduledPartialMessages)
        );

        if ($this->collectionIndexationHandler instanceof ReindexHandlerWithFieldGroups) {
            $this->collectionIndexationHandler->handleWithFieldGroups(
                $scheduledPartialMessages,
                $rootJobName,
                false,
                ['main']
            );
        } else {
            $this->collectionIndexationHandler->handle($scheduledPartialMessages, $rootJobName);
        }
    }

    /**
     * @param Segment $segment
     * @param bool $isFull
     * @param array $additionalProducts
     */
    public function scheduleSegment(Segment $segment, $isFull = false, array $additionalProducts = [])
    {
        if (!array_key_exists($segment->getId(), $this->segments)) {
            $this->segments[$segment->getId()] = [
                self::SEGMENT => $segment,
                self::IS_FULL => $isFull,
                self::ADDITIONAL_PRODUCTS => $additionalProducts,
            ];
        }

        if ($isFull) {
            $this->segments[$segment->getId()][self::IS_FULL] = true;
        }

        if ($additionalProducts) {
            $this->segments[$segment->getId()][self::ADDITIONAL_PRODUCTS] = array_unique(array_merge(
                $additionalProducts,
                $this->segments[$segment->getId()][self::ADDITIONAL_PRODUCTS]
            ));
        }
    }

    public function scheduleMessageBySegmentDefinition(Segment $segment)
    {
        $websiteIds = $this->productCollectionSegmentHelper->getWebsiteIdsBySegment($segment);
        if (count($websiteIds) > 0) {
            $message = $this->accumulateMessageFactory->getPartialMessageData(
                $websiteIds,
                null,
                $segment->getDefinition(),
                true
            );
            $this->scheduledPartialMessages[$this->getMessageKey($message)] = $message;
        }
    }

    private function addSegmentsMessages()
    {
        while ($segmentData = array_pop($this->segments)) {
            $segment = $segmentData[self::SEGMENT];
            $websiteIds = $this->productCollectionSegmentHelper->getWebsiteIdsBySegment($segment);
            if (count($websiteIds) > 0) {
                $message = $this->accumulateMessageFactory->getPartialMessageData(
                    $websiteIds,
                    $segment,
                    null,
                    $segmentData[self::IS_FULL],
                    $segmentData[self::ADDITIONAL_PRODUCTS]
                );
                $this->scheduledPartialMessages[$this->getMessageKey($message)] = $message;
            }
        }
    }

    private function getMessageKey(array $message): string
    {
        return md5(json_encode($message));
    }
}
