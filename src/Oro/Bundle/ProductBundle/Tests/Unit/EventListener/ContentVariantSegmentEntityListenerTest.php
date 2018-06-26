<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionAwareContentVariantEntityListener;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionVariantReindexMessageSendListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentVariantSegmentEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ProductCollectionVariantReindexMessageSendListener|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reindexEventListener;

    /**
     * @var ProductCollectionAwareContentVariantEntityListener
     */
    private $segmentEntityListener;

    protected function setUp()
    {
        $this->reindexEventListener = $this->createMock(ProductCollectionVariantReindexMessageSendListener::class);

        $this->segmentEntityListener = new ProductCollectionAwareContentVariantEntityListener(
            $this->reindexEventListener
        );
    }

    public function testPostPersistWhenWebCatalogIsOn()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->reindexEventListener
            ->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);

        $this->segmentEntityListener->postPersist($segment);
    }

    public function testPostUpdateDefinitionNotChanged()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->reindexEventListener
            ->expects($this->never())
            ->method('scheduleSegment');
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('definition')
            ->willReturn(false);

        $this->segmentEntityListener->preUpdate($segment, $event);
    }

    public function testPostUpdateDefinitionChanged()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->reindexEventListener
            ->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('definition')
            ->willReturn(true);

        $this->segmentEntityListener->preUpdate($segment, $event);
    }

    public function testPreRemove()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->reindexEventListener
            ->expects($this->once())
            ->method('scheduleMessageBySegmentDefinition')
            ->with($segment);

        $this->segmentEntityListener->preRemove($segment);
    }
}
