<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\EventListener\ContentVariantSegmentListener;
use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentVariantSegmentListenerTest extends \PHPUnit_Framework_TestCase
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
     * @var ContentVariantSegmentListener
     */
    private $listener;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(SegmentMessageFactory::class);
        $this->productCollectionSegmentHelper = $this->createMock(ProductCollectionSegmentHelper::class);
        $this->listener = new ContentVariantSegmentListener(
            $this->messageProducer,
            $this->messageFactory,
            $this->productCollectionSegmentHelper
        );
    }

    public function testListener()
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
        $message = json_encode(['id' => 1, 'website_ids' => $websiteIds]);
        $this->messageFactory->expects($this->once())
            ->method('createMessage')
            ->with($segmentWithWebsite, $websiteIds)
            ->willReturn($message);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT, $message);

        $this->listener->postFlush();
    }
}
