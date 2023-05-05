<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\EventListener\ProductSearchHistoryEventListener;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\SearchResultHistoryManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductSearchHistoryEventListenerTest extends TestCase
{
    /**
     * @var FeatureChecker|MockObject
     */
    private $featureChecker;

    /**
     * @var SearchProductHandler|MockObject
     */
    private $searchProductHandler;

    /**
     * @var SearchResultHistoryManagerInterface|MockObject
     */
    private $searchResultHistoryManager;
    private ProductSearchHistoryEventListener $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->searchProductHandler = $this->createMock(SearchProductHandler::class);
        $this->searchResultHistoryManager = $this->createMock(SearchResultHistoryManagerInterface::class);

        $this->listener = new ProductSearchHistoryEventListener($this->searchResultHistoryManager);
        $this->listener->setSearchProductHandler($this->searchProductHandler);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addSupportedSearchQueryType('test');
        $this->listener->addFeature('test_feature');
    }

    /**
     * @dataProvider termsDataProvider
     */
    public function testOnSearchAfter(string $queryTerm, string $hintTerm, string $expectedTerm)
    {
        $this->searchProductHandler->expects($this->once())
            ->method('getSearchString')
            ->willReturn($queryTerm);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $result = $this->createMock(Result::class);
        $result->expects($this->any())
            ->method('getRecordsCount')
            ->willReturn(0);
        $query = $this->createMock(Query::class);
        $query->expects($this->any())
            ->method('getHint')
            ->willReturnMap([
                [Query::HINT_SEARCH_TYPE, 'test'],
                [Query::HINT_SEARCH_TERM, $hintTerm],
            ]);
        $event = $this->createMock(AfterSearchEvent::class);
        $event->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $event->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->searchResultHistoryManager->expects($this->once())
            ->method('saveSearchResult')
            ->with(
                $expectedTerm,
                'empty',
                0
            );

        $this->listener->onSearchAfter($event);
    }

    public function termsDataProvider(): array
    {
        return [
            ['query_term', 'hint_term', 'query_term'],
            ['', 'hint_term', 'hint_term'],
        ];
    }
}
