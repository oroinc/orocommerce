<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\EventListener\ContentVariantSegmentEntityListener;
use Oro\Bundle\ProductBundle\EventListener\ContentVariantSegmentListener;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

class ContentVariantSegmentEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantSegmentListener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentVariantSegmentListener;

    /**
     * @var WebCatalogUsageProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $webCatalogUsageProvider;

    /**
     * @var ContentVariantSegmentEntityListener
     */
    private $segmentEntityListener;

    protected function setUp()
    {
        $this->contentVariantSegmentListener = $this->getMockBuilder(ContentVariantSegmentListener::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $this->segmentEntityListener = new ContentVariantSegmentEntityListener(
            $this->contentVariantSegmentListener,
            $this->webCatalogUsageProvider
        );
    }

    public function testPostPersistWhenWebCatalogIsOff()
    {
        $this->segmentEntityListener = new ContentVariantSegmentEntityListener($this->contentVariantSegmentListener);

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->contentVariantSegmentListener
            ->expects($this->never())
            ->method('scheduleSegment');

        $this->segmentEntityListener->postPersist($segment);
    }

    public function testPostPersistWhenWebCatalogIsOn()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->contentVariantSegmentListener
            ->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);

        $this->segmentEntityListener->postPersist($segment);
    }

    public function testPostUpdateWhenWebCatalogIsOff()
    {
        $this->segmentEntityListener = new ContentVariantSegmentEntityListener($this->contentVariantSegmentListener);

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->contentVariantSegmentListener
            ->expects($this->never())
            ->method('scheduleSegment');
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);

        $this->segmentEntityListener->preUpdate($segment, $event);
    }

    public function testPostUpdateWhenWebCatalogIsOnAndDefinitionNotChanged()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->contentVariantSegmentListener
            ->expects($this->never())
            ->method('scheduleSegment');
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('definition')
            ->willReturn(false);

        $this->segmentEntityListener->preUpdate($segment, $event);
    }

    public function testPostUpdateWhenWebCatalogIsOnAndDefinitionChanged()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class);

        $this->contentVariantSegmentListener
            ->expects($this->once())
            ->method('scheduleSegment')
            ->with($segment);
        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('definition')
            ->willReturn(true);

        $this->segmentEntityListener->preUpdate($segment, $event);
    }
}
