<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchSegmentListener;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchSegmentListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantSegmentProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentVariantSegmentProvider;

    /**
     * @var StaticSegmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $staticSegmentManager;

    /**
     * @var WebsiteSearchSegmentListener
     */
    private $websiteSearchSegmentListener;

    protected function setUp()
    {
        $this->contentVariantSegmentProvider = $this->getMockBuilder(ContentVariantSegmentProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->staticSegmentManager = $this->getMockBuilder(StaticSegmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteSearchSegmentListener = new WebsiteSearchSegmentListener(
            $this->contentVariantSegmentProvider,
            $this->staticSegmentManager
        );
    }

    public function testOnWebsiteSearchIndexWhenNoContentVariantSegments()
    {
        $event = new IndexEntityEvent(Product::class, [$this->getEntity(Product::class, ['id' => 1])], []);

        $this->contentVariantSegmentProvider
            ->expects($this->once())
            ->method('getContentVariantSegments')
            ->willReturn([]);

        $this->staticSegmentManager
            ->expects($this->never())
            ->method('run');

        $this->websiteSearchSegmentListener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexWithUnsupportedEntity()
    {
        $event = new IndexEntityEvent(ProductImage::class, [$this->getEntity(ProductImage::class, ['id' => 1])], []);

        $this->contentVariantSegmentProvider
            ->expects($this->never())
            ->method('getContentVariantSegments');

        $this->staticSegmentManager
            ->expects($this->never())
            ->method('run');

        $this->websiteSearchSegmentListener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexWhenContentVariantSegmentsExist()
    {
        $entityIds = [1, 3, 5];
        $entities = [
            $this->getEntity(Product::class, ['id' => 1]),
            $this->getEntity(Product::class, ['id' => 3]),
            $this->getEntity(Product::class, ['id' => 5])
        ];
        $event = new IndexEntityEvent(Product::class, $entities, []);

        $firstSegment = $this->getEntity(Segment::class, ['id' => 1]);
        $secondSegment = $this->getEntity(Segment::class, ['id' => 2]);

        $this->contentVariantSegmentProvider
            ->expects($this->once())
            ->method('getContentVariantSegments')
            ->willReturn([$firstSegment, $secondSegment]);

        $this->staticSegmentManager
            ->expects($this->exactly(2))
            ->method('run')
            ->withConsecutive(
                [$firstSegment, $entityIds],
                [$secondSegment, $entityIds]
            );

        $this->websiteSearchSegmentListener->onWebsiteSearchIndex($event);
    }
}
