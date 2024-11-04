<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm\ProductCollection;

// phpcs:disable Generic.Files.LineLength.TooLong
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection\ProductCollectionSearchTermWebsiteSearchIndexEntityListener;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsIndexDataProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductCollectionSearchTermWebsiteSearchIndexEntityListenerTest extends TestCase
{
    use ContextTrait;

    public const ASSIGN_TYPE_SEARCH_TERM = 'search_term';

    private SearchTermsIndexDataProvider|MockObject $searchTermsIndexDataProvider;

    private WebsiteContextManager|MockObject $websiteContextManager;

    private ProductCollectionSearchTermWebsiteSearchIndexEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchTermsIndexDataProvider = $this->createMock(SearchTermsIndexDataProvider::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);

        $this->listener = new ProductCollectionSearchTermWebsiteSearchIndexEntityListener(
            $this->searchTermsIndexDataProvider,
            $this->websiteContextManager
        );
    }

    public function testWhenUnsupportedFieldsGroup(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['image'];

        $this->searchTermsIndexDataProvider
            ->expects(self::never())
            ->method('getSearchTermsDataForProducts');

        $event = new IndexEntityEvent(Product::class, [], $context);
        self::assertSame([], $event->getEntitiesData());

        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame([], $event->getEntitiesData());
    }

    public function testWhenEntityClassNotProduct(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['main'];

        $this->searchTermsIndexDataProvider
            ->expects(self::never())
            ->method('getSearchTermsDataForProducts');

        $event = new IndexEntityEvent(\stdClass::class, [], $context);
        self::assertSame([], $event->getEntitiesData());

        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame([], $event->getEntitiesData());
    }

    public function testWhenNoSearchTermsData(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['main'];

        $product1 = (new ProductStub())->setId(42);
        $event = new IndexEntityEvent(Product::class, [$product1], $context);
        self::assertSame([], $event->getEntitiesData());

        $this->searchTermsIndexDataProvider
            ->expects(self::once())
            ->method('getSearchTermsDataForProducts')
            ->with([$product1])
            ->willReturn([]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame([], $event->getEntitiesData());
    }

    /**
     * @dataProvider invalidSearchTermsDataProvider
     */
    public function testWhenHasInvalidSearchTermsData(array $invalidSearchTermData): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['main'];

        $product1 = (new ProductStub())->setId(42);
        $event = new IndexEntityEvent(Product::class, [$product1], $context);
        self::assertSame([], $event->getEntitiesData());

        $this->searchTermsIndexDataProvider
            ->expects(self::once())
            ->method('getSearchTermsDataForProducts')
            ->with([$product1])
            ->willReturn([$invalidSearchTermData]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame([], $event->getEntitiesData());
    }

    public function invalidSearchTermsDataProvider(): \Generator
    {
        yield 'no productCollectionProductId' => [
            [
                'searchTermId' => 42,
                'productCollectionSegmentId' => 142,
            ],
        ];

        yield 'no productCollectionSegmentId' => [
            [
                'searchTermId' => 42,
                'productCollectionProductId' => 242,
            ],
        ];

        yield 'no searchTermId' => [
            [
                'productCollectionSegmentId' => 142,
                'productCollectionProductId' => 242,
            ],
        ];
    }

    public function testWhenHasValidSearchTermsData(): void
    {
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = [];
        $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = ['main'];

        $productId = 242;
        $product1 = (new ProductStub())->setId($productId);
        $event = new IndexEntityEvent(Product::class, [$product1], $context);
        self::assertSame([], $event->getEntitiesData());

        $searchTermId = 42;
        $segmentId = 142;
        $this->searchTermsIndexDataProvider
            ->expects(self::once())
            ->method('getSearchTermsDataForProducts')
            ->with([$product1])
            ->willReturn([
                [
                    'searchTermId' => $searchTermId,
                    'productCollectionSegmentId' => $segmentId,
                    'productCollectionProductId' => $productId,
                ],
            ]);

        $this->listener->onWebsiteSearchIndex($event);

        self::assertEquals(
            [
                $productId => [
                    'assigned_to.ASSIGN_TYPE_ASSIGN_ID' => [
                        [
                            'value' => new PlaceholderValue(
                                $segmentId,
                                [
                                    AssignTypePlaceholder::NAME => 'search_term',
                                    AssignIdPlaceholder::NAME => $searchTermId,
                                ]
                            ),
                            'all_text' => false,
                        ],
                    ],
                ],
            ],
            $event->getEntitiesData()
        );
    }
}
