<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductCollectionVariantReindexMessageSendListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var SegmentMessageFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageFactory;

    /**
     * @var ProductCollectionSegmentHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productCollectionSegmentHelper;

    /**
     * @var ProductCollectionVariantReindexMessageSendListener
     */
    protected $listener;

    protected function setUp()
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

        $message = [
            SegmentMessageFactory::WEBSITE_IDS => $websiteIds,
            SegmentMessageFactory::ID => 42,
            SegmentMessageFactory::DEFINITION => null
        ];
        $this->messageFactory->expects($this->exactly(2))
            ->method('createMessage')
            ->with($websiteIds, null, $definition)
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

        $this->listener->scheduleSegment($segmentWithWebsite);
        $this->listener->scheduleSegment($segmentWithoutWebsite);

        $websiteIds = [1, 3];

        $this->productCollectionSegmentHelper->expects($this->exactly(2))
            ->method('getWebsiteIdsBySegment')
            ->willReturnMap(
                [
                    [$segmentWithWebsite, $websiteIds],
                    [$segmentWithoutWebsite, []]
                ]
            );
        $message = ['id' => 1, 'website_ids' => $websiteIds];
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($websiteIds, $segmentWithWebsite)
            ->willReturn($message);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $message);

        $this->listener->postFlush();
    }
}
