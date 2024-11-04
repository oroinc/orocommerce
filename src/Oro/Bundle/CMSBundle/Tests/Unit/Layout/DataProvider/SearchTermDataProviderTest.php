<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Layout\DataProvider\SearchTermDataProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\SearchTerm;
use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SearchTermDataProviderTest extends TestCase
{
    private SearchTermProvider|MockObject $searchTermProvider;

    private SearchTermDataProvider $searchTermDataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchTermProvider = $this->createMock(SearchTermProvider::class);

        $this->searchTermDataProvider = new SearchTermDataProvider(
            $this->searchTermProvider
        );
    }

    /**
     * @dataProvider getSearchTermContentBlockAliasDataProvider
     */
    public function testGetSearchTermContentBlockAlias(?SearchTerm $searchTerm, ?string $expectedResult): void
    {
        $search = 'foo';

        $this->searchTermProvider->expects(self::once())
            ->method('getMostSuitableSearchTerm')
            ->with($search)
            ->willReturn($searchTerm);

        self::assertEquals($expectedResult, $this->searchTermDataProvider->getSearchTermContentBlockAlias($search));
    }

    public function getSearchTermContentBlockAliasDataProvider(): array
    {
        return [
            'no SearchTerm' => [
                'searchTerm' => null,
                'expectedResult' => null,
            ],
            'SearchTerm without ContentBlock' => [
                'searchTerm' => new SearchTerm(),
                'expectedResult' => null,
            ],
            'ContentBlock without alias' => [
                'searchTerm' => (new SearchTerm())->setContentBlock(new ContentBlock()),
                'expectedResult' => null,
            ],
            'ContentBlock with alias' => [
                'searchTerm' => (new SearchTerm())->setContentBlock((new ContentBlock())->setAlias('bar')),
                'expectedResult' => 'bar',
            ],
        ];
    }
}
