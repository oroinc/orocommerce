<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandlerInterface;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Listener that sends product segment data to reindex.
 */
class ProductCollectionVariantReindexMessageSendListener
{
    private const SEGMENT = 'segment';
    private const IS_FULL = 'is_full';
    private const ADDITIONAL_PRODUCTS = 'additional_products';

    private AsyncReindexProductCollectionHandlerInterface $collectionIndexationHandler;
    private SegmentMessageFactory $messageFactory;
    private ProductCollectionSegmentHelper $productCollectionSegmentHelper;
    private array $scheduledPartialMessages = [];
    private array $segments = [];

    public function __construct(
        AsyncReindexProductCollectionHandlerInterface $collectionIndexationHandler,
        ProductCollectionSegmentHelper $productCollectionSegmentHelper,
        SegmentMessageFactory $messageFactory,
    ) {
        $this->collectionIndexationHandler = $collectionIndexationHandler;
        $this->productCollectionSegmentHelper = $productCollectionSegmentHelper;
        $this->messageFactory = $messageFactory;
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
            ReindexProductCollectionBySegmentTopic::NAME,
            'listener',
            $this->getMessageKey($scheduledPartialMessages)
        );
        $this->collectionIndexationHandler->handle(
            $scheduledPartialMessages,
            $rootJobName,
            false,
            ['main', 'collection_sort_order']
        );
    }

    /**
     * @param Segment $segment
     * @param bool $isFull
     * @param array $additionalProducts
     */
    public function scheduleSegment(Segment $segment, bool $isFull = false, array $additionalProducts = []): void
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
            $message = $this->messageFactory->getPartialMessageData(
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
                $message = $this->messageFactory->getPartialMessageData(
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
