<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductCollectionVariantReindexMessageSendListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageProducer;

    /**
     * @var SegmentMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var ProductCollectionSegmentHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productCollectionSegmentHelper;

    /**
     * @var ProductCollectionVariantReindexMessageSendListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(SegmentMessageFactory::class);
        $this->productCollectionSegmentHelper = $this->createMock(ProductCollectionSegmentHelper::class);
        $this->listener = new ProductCollectionVariantReindexMessageSendListener(
            $this->messageProducer,
            $this->productCollectionSegmentHelper,
            $this->messageFactory
        );
    }

    public function testPostFlushWithoutMessage()
    {
        $this->messageProducer->expects($this->never())
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
            SegmentMessageFactory::WEBSITE_IDS => $websiteIds,
            SegmentMessageFactory::ID => 42,
            SegmentMessageFactory::DEFINITION => null,
            SegmentMessageFactory::IS_FULL => $isFull,
        ];
        $this->messageFactory->expects($this->exactly(2))
            ->method('createMessage')
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

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $message);

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
        $this->messageFactory->expects($this->exactly(2))
            ->method('createMessage')
            ->withConsecutive(
                [$websiteIds, $segmentWithWebsiteWithIsFull, null, $isFull],
                [$websiteIds, $segmentWithWebsite, null, false, [42]]
            )
            ->willReturnOnConsecutiveCalls($messageForSegmentWithWebsiteWithIsFull, $messageForSegmentWithWebsite);
        $this->messageProducer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $messageForSegmentWithWebsite],
                [Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $messageForSegmentWithWebsiteWithIsFull]
            );

        $this->listener->postFlush();
    }
}
