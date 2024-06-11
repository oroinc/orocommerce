<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ProductCollection;

// phpcs:disable Generic.Files.LineLength.TooLong
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection\ProductCollectionSearchTermBeforeReindexEventListener;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermProductCollectionSegmentsProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductCollectionSearchTermBeforeReindexEventListenerTest extends TestCase
{
    use ContextTrait;

    private StaticSegmentManager|MockObject $staticSegmentManager;

    private SearchTermProductCollectionSegmentsProvider|MockObject $searchTermProductCollectionSegmentsProvider;

    private ProductCollectionSearchTermBeforeReindexEventListener $listener;

    protected function setUp(): void
    {
        $this->staticSegmentManager = $this->createMock(StaticSegmentManager::class);
        $this->searchTermProductCollectionSegmentsProvider = $this->createMock(
            SearchTermProductCollectionSegmentsProvider::class
        );

        $this->listener = new ProductCollectionSearchTermBeforeReindexEventListener(
            $this->staticSegmentManager,
            $this->searchTermProductCollectionSegmentsProvider
        );
    }

    /**
     * @dataProvider processWithoutProductEntityProvider
     */
    public function testWhenWithoutProductEntity(string|array $classOrClasses): void
    {
        $this->searchTermProductCollectionSegmentsProvider
            ->expects(self::never())
            ->method('getSearchTermProductCollectionSegments');

        $this->staticSegmentManager
            ->expects(self::never())
            ->method('run');

        $event = new BeforeReindexEvent($classOrClasses);
        $this->listener->onBeforeReindex($event);
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

    public function testWhenUnsupportedFieldsGroup(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = [1, 2];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['image'];

        $this->searchTermProductCollectionSegmentsProvider
            ->expects(self::never())
            ->method('getSearchTermProductCollectionSegments');

        $this->staticSegmentManager
            ->expects(self::never())
            ->method('run');

        $event = new BeforeReindexEvent(Product::class, $context);
        $this->listener->onBeforeReindex($event);
    }

    /**
     * @dataProvider onBeforeReindexDataProvider
     */
    public function testOnBeforeReindex(string|array $classOrClasses, array $context): void
    {
        $segment1 = new Segment();
        $segment2 = new Segment();

        $this->searchTermProductCollectionSegmentsProvider
            ->expects(self::once())
            ->method('getSearchTermProductCollectionSegments')
            ->willReturn([$segment1, $segment2]);

        $this->staticSegmentManager
            ->expects(self::exactly(2))
            ->method('run')
            ->withConsecutive(
                [$segment1, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]],
                [$segment2, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]]
            );

        $event = new BeforeReindexEvent($classOrClasses, $context);
        $this->listener->onBeforeReindex($event);
    }

    public function onBeforeReindexDataProvider(): array
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
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main'],
                ],
            ],
        ];
    }

    public function testOnBeforeReindexWithWebsite(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_WEBSITE_IDS] = [1, 2];
        $segment1 = new Segment();
        $segment2 = new Segment();

        $this->searchTermProductCollectionSegmentsProvider
            ->expects(self::exactly(2))
            ->method('getSearchTermProductCollectionSegments')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls([$segment1], [$segment2]);

        $this->staticSegmentManager
            ->expects(self::exactly(2))
            ->method('run')
            ->withConsecutive(
                [$segment1, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]],
                [$segment2, $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY]]
            );
        $event = new BeforeReindexEvent(Product::class, $context);
        $this->listener->onBeforeReindex($event);
    }
}
