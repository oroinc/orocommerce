<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Async\Topic\AccumulateReindexProductCollectionBySegmentTopic as Topic;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Bundle\ProductBundle\Handler\AsyncReindexProductCollectionHandler;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\AccumulateSegmentMessageFactory;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductCollectionVariantReindexMessageSendListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AsyncReindexProductCollectionHandler|\PHPUnit\Framework\MockObject\MockObject */
    private AsyncReindexProductCollectionHandler $collectionIndexationHandler;

    /** @var AccumulateSegmentMessageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private AccumulateSegmentMessageFactory $accumulateMessageFactory;

    /** @var ProductCollectionSegmentHelper|\PHPUnit\Framework\MockObject\MockObject */
    private ProductCollectionSegmentHelper $productCollectionSegmentHelper;
    protected ProductCollectionVariantReindexMessageSendListener $listener;

    protected function setUp(): void
    {
        $this->collectionIndexationHandler = $this->createMock(AsyncReindexProductCollectionHandler::class);
        $this->accumulateMessageFactory = $this->createMock(AccumulateSegmentMessageFactory::class);
        $this->productCollectionSegmentHelper = $this->createMock(ProductCollectionSegmentHelper::class);
        $this->listener = new ProductCollectionVariantReindexMessageSendListener(
            $this->createMock(MessageProducerInterface::class),
            $this->productCollectionSegmentHelper,
            $this->createMock(SegmentMessageFactory::class)
        );

        $this->listener->setAccumulateMessageFactory($this->accumulateMessageFactory);
        $this->listener->setReindexHandler($this->collectionIndexationHandler);
    }

    public function testPostFlushWithoutMessage()
    {
        $this->collectionIndexationHandler
            ->expects($this->never())
            ->method($this->anything());

        $this->listener->postFlush();
    }

    public function testScheduleMessageBySegmentDefinition()
    {
        $websiteIds = [1, 2];
        $definition = json_encode(['columns' => [], 'filters' => []]);
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => 42, 'definition' => $definition]);
        /** @var Segment $segmentWithoutWebsites */
        $segmentWithoutWebsites = $this->getEntity(Segment::class, ['id' => 41, 'definition' => $definition]);

        $isFull = true;
        $message = [
            Topic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            Topic::OPTION_NAME_ID => 42,
            Topic::OPTION_NAME_DEFINITION => null,
            Topic::OPTION_NAME_IS_FULL => $isFull,
        ];
        $this->accumulateMessageFactory->expects($this->exactly(2))
            ->method('getPartialMessageData')
            ->with($websiteIds, null, $definition, $isFull)
            ->willReturn($message);
        $this->productCollectionSegmentHelper->expects($this->exactly(3))
            ->method('getWebsiteIdsBySegment')
            ->withConsecutive(
                [$segment],
                [$segment],
                [$segmentWithoutWebsites]
            )
            ->willReturnOnConsecutiveCalls(
                $websiteIds,
                $websiteIds,
                []
            );

        $this->listener->scheduleMessageBySegmentDefinition($segment);
        $this->listener->scheduleMessageBySegmentDefinition($segment);
        $this->listener->scheduleMessageBySegmentDefinition($segmentWithoutWebsites);

        $scheduledPartialMessages = [
            '2d2d279e7a45800b5a733f31bb2b834b' => $message
        ];
        $this->collectionIndexationHandler
            ->expects($this->once())
            ->method('handleWithFieldGroups')
            ->with(
                $scheduledPartialMessages,
                'oro_product.accumulate_reindex_product_collection_by_segment'
                . ':listener:433143095836f9cab5eb7d9c9374cab5',
                false,
                ['main']
            );

        $this->listener->postFlush();
    }

    public function testScheduleSegment()
    {
        /** @var Segment $segmentWithWebsite */
        $segmentWithWebsite = $this->getEntity(Segment::class, ['id' => 1]);
        /** @var Segment $segmentWithoutWebsite */
        $segmentWithoutWebsite = $this->getEntity(Segment::class, ['id' => 2]);
        /** @var Segment $segmentWithWebsiteWithIsFull */
        $segmentWithWebsiteWithIsFull = $this->getEntity(Segment::class, ['id' => 3]);

        $this->listener->scheduleSegment($segmentWithWebsite);
        $this->listener->scheduleSegment($segmentWithoutWebsite);
        $isFull = true;
        $this->listener->scheduleSegment($segmentWithWebsiteWithIsFull, $isFull);
        $this->listener->scheduleSegment($segmentWithWebsiteWithIsFull, false);

        $this->listener->scheduleSegment($segmentWithWebsite, false, [42]);

        $websiteIds = [1, 3];

        $this->productCollectionSegmentHelper->expects($this->exactly(3))
            ->method('getWebsiteIdsBySegment')
            ->willReturnMap(
                [
                    [$segmentWithWebsite, $websiteIds],
                    [$segmentWithoutWebsite, []],
                    [$segmentWithWebsiteWithIsFull, $websiteIds],
                ]
            );
        $messageForSegmentWithWebsiteWithIsFull = ['id' => 3, 'website_ids' => $websiteIds, 'is_full' => $isFull];
        $messageForSegmentWithWebsite = ['id' => 1, 'website_ids' => $websiteIds, 'is_full' => false];
        $this->accumulateMessageFactory->expects($this->exactly(2))
            ->method('getPartialMessageData')
            ->withConsecutive(
                [$websiteIds, $segmentWithWebsiteWithIsFull, null, $isFull],
                [$websiteIds, $segmentWithWebsite, null, false, [42]]
            )
            ->willReturnOnConsecutiveCalls($messageForSegmentWithWebsiteWithIsFull, $messageForSegmentWithWebsite);

        $scheduledPartialMessages = [
            '51b6e37da8607199599a41970514e0e0' => $messageForSegmentWithWebsiteWithIsFull,
            '08f6b7397879d6bfcb7b7b55d8a076da' => $messageForSegmentWithWebsite,
        ];
        $this->collectionIndexationHandler
            ->expects($this->once())
            ->method('handleWithFieldGroups')
            ->with(
                $scheduledPartialMessages,
                'oro_product.accumulate_reindex_product_collection_by_segment'
                . ':listener:823a241bbe36fd0f41c4b4dd1f838185',
                false,
                ['main']
            );

        $this->listener->postFlush();
    }
}
