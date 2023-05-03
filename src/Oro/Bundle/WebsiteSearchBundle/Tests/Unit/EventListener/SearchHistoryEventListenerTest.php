<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\SearchHistoryEventListener;
use Oro\Bundle\WebsiteSearchBundle\Manager\SearchResultHistoryManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchHistoryEventListenerTest extends TestCase
{
    /**
     * @var FeatureChecker|MockObject
     */
    private $featureChecker;

    /**
     * @var SearchResultHistoryManagerInterface|MockObject
     */
    private $searchResultHistoryManager;
    private SearchHistoryEventListener $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->searchResultHistoryManager = $this->createMock(SearchResultHistoryManagerInterface::class);

        $this->listener = new SearchHistoryEventListener($this->searchResultHistoryManager);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('test_feature');
    }

    public function testOnSearchAfterFeatureDisabled()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->listener->addSupportedSearchQueryType('test');
        $event = $this->getEvent('term', 'test', 0);

        $this->searchResultHistoryManager->expects($this->never())
            ->method('saveSearchResult');

        $this->listener->onSearchAfter($event);
    }

    public function testOnSearchAfterUnsupportedType()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->listener->addSupportedSearchQueryType('test');
        $event = $this->getEvent('term', 'test2', 0);

        $this->searchResultHistoryManager->expects($this->never())
            ->method('saveSearchResult');

        $this->listener->onSearchAfter($event);
    }

    public function testOnSearchAfterNoSearchTerm()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->listener->addSupportedSearchQueryType('test');
        $event = $this->getEvent('', 'test', 0);

        $this->searchResultHistoryManager->expects($this->never())
            ->method('saveSearchResult');

        $this->listener->onSearchAfter($event);
    }

    public function testOnSearchAfterEmpty()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->listener->addSupportedSearchQueryType('test');
        $event = $this->getEvent('term', 'test', 0);

        $this->searchResultHistoryManager->expects($this->once())
            ->method('saveSearchResult')
            ->with(
                'term',
                'empty',
                0
            );

        $this->listener->onSearchAfter($event);
    }

    public function testOnSearchAfterNotEmpty()
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->listener->addSupportedSearchQueryType('test');
        $event = $this->getEvent('term', 'test', 10);

        $this->searchResultHistoryManager->expects($this->once())
            ->method('saveSearchResult')
            ->with(
                'term',
                'test',
                10
            );

        $this->listener->onSearchAfter($event);
    }

    private function getEvent(string $searchTerm, string $searchType, int $resultsCount): AfterSearchEvent
    {
        $result = $this->createMock(Result::class);
        $result->expects($this->any())
            ->method('getRecordsCount')
            ->willReturn($resultsCount);
        $query = $this->createMock(Query::class);
        $query->expects($this->any())
            ->method('getHint')
            ->willReturnMap([
                [Query::HINT_SEARCH_TYPE, $searchType],
                [Query::HINT_SEARCH_TERM, $searchTerm],
            ]);
        $query->expects($this->any())
            ->method('hasHint')
            ->willReturn(true);
        $event = $this->createMock(AfterSearchEvent::class);
        $event->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $event->expects($this->any())
            ->method('getResult')
            ->willReturn($result);

        return $event;
    }
}
