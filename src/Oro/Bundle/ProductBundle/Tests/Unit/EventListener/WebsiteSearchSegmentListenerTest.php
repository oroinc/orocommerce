<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchSegmentListener;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchSegmentListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentVariantSegmentProvider;

    /**
     * @var StaticSegmentManager|\PHPUnit\Framework\MockObject\MockObject
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

    public function testOnWebsiteSearchIndexWhenContentVariantSegmentsExistForWebsite()
    {
        $entityIds = [1, 5];
        $entities = [
            $this->getEntity(Product::class, ['id' => 1]),
            $this->getEntity(Product::class, ['id' => 5])
        ];
        $event = new IndexEntityEvent(
            Product::class,
            $entities,
            [
                AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 42,
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [42]
            ]
        );

        $segment = $this->getEntity(Segment::class, ['id' => 1]);

        $this->contentVariantSegmentProvider
            ->expects($this->once())
            ->method('getContentVariantSegmentsByWebsiteId')
            ->with(42)
            ->willReturn([$segment]);

        $this->staticSegmentManager
            ->expects($this->once())
            ->method('run')
            ->with($segment, $entityIds);

        $this->websiteSearchSegmentListener->onWebsiteSearchIndex($event);
    }

    /**
     * @dataProvider processWithoutProductEntityProvider
     * @param mixed $classOrClasses
     */
    public function testProcessWithoutProductEntity($classOrClasses)
    {
        $this->contentVariantSegmentProvider->expects($this->never())
            ->method('getContentVariantSegments');
        $this->staticSegmentManager->expects($this->never())
            ->method('run');

        $event = new BeforeReindexEvent($classOrClasses);
        $this->websiteSearchSegmentListener->process($event);
    }

    public function processWithoutProductEntityProvider()
    {
        return [
            'without product in array' => [
                [\stdClass::class],
            ],
            'not a product in string' => [
                \stdClass::class,
            ],
        ];
    }

    /**
     * @dataProvider processProvider
     * @param mixed $classOrClasses
     * @param array $context
     */
    public function testProcess($classOrClasses, array $context)
    {
        $segment1 = new Segment();
        $segment2 = new Segment();
        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('getContentVariantSegments')
            ->willReturn([$segment1, $segment2]);
        $this->staticSegmentManager->expects($this->exactly(2))
            ->method('run')
            ->withConsecutive(
                [$segment1, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]],
                [$segment2, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]]
            );

        $event = new BeforeReindexEvent($classOrClasses, $context);
        $this->websiteSearchSegmentListener->process($event);
    }

    public function processProvider()
    {
        return [
            'with empty classes and empty ids' => [
                [],
                [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => []],
            ],
            'with product class in array and filled ids' => [
                [Product::class],
                [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [333, 777]],
            ],
            'with product class and filled ids' => [
                Product::class,
                [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [333, 777]],
            ]
        ];
    }
}
