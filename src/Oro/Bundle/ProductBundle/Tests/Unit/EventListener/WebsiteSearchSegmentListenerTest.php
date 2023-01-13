<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchSegmentListener;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchSegmentListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentVariantSegmentProvider;

    /** @var StaticSegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $staticSegmentManager;

    /** @var WebsiteSearchSegmentListener */
    private $websiteSearchSegmentListener;

    protected function setUp(): void
    {
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->staticSegmentManager = $this->createMock(StaticSegmentManager::class);

        $this->websiteSearchSegmentListener = new WebsiteSearchSegmentListener(
            $this->contentVariantSegmentProvider,
            $this->staticSegmentManager
        );
    }

    /**
     * @dataProvider processWithoutProductEntityProvider
     */
    public function testProcessWithoutProductEntity(string|array $classOrClasses)
    {
        $this->contentVariantSegmentProvider->expects($this->never())
            ->method('getContentVariantSegments');
        $this->staticSegmentManager->expects($this->never())
            ->method('run');
        $event = new BeforeReindexEvent($classOrClasses);
        $this->websiteSearchSegmentListener->process($event);
    }

    public function processWithoutProductEntityProvider(): array
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

    public function testProcessUnsupportedFieldsGroup()
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = [1, 2];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['image'];

        $this->contentVariantSegmentProvider->expects($this->never())
            ->method('getContentVariantSegments');
        $this->staticSegmentManager->expects($this->never())
            ->method('run');

        $event = new BeforeReindexEvent(Product::class, $context);
        $this->websiteSearchSegmentListener->process($event);
    }

    public function testProcessForWebsite()
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = [1, 2];
        $segment1 = new Segment();
        $segment2 = new Segment();
        $this->contentVariantSegmentProvider->expects($this->exactly(2))
            ->method('getContentVariantSegmentsByWebsiteId')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls([$segment1], [$segment2]);
        $this->staticSegmentManager->expects($this->exactly(2))
            ->method('run')
            ->withConsecutive(
                [$segment1, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]],
                [$segment2, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]]
            );
        $event = new BeforeReindexEvent(Product::class, $context);
        $this->websiteSearchSegmentListener->process($event);
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess(string|array $classOrClasses, array $context)
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

    public function processProvider(): array
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
            ],
            'with product class and filled ids main fields group' => [
                Product::class,
                [
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [333, 777],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']
                ],
            ]
        ];
    }
}
