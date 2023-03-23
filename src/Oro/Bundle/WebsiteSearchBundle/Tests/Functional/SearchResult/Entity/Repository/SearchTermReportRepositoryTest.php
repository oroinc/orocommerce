<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchResult\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryData;

class SearchTermReportRepositoryTest extends WebTestCase
{
    private SearchTermReportRepository $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSearchResultHistoryData::class]);
        $this->repo = self::getContainer()->get('doctrine')->getRepository(SearchTermReport::class);
    }

    public function testUpsertSearchHistoryRecord()
    {
        $this->repo->actualizeReport();

        $actual = [];
        foreach ($this->repo->findBy([], ['searchTerm' => 'ASC']) as $record) {
            $actual[] = [
                'term' => $record->getSearchTerm(),
                'times_searched' => $record->getTimesSearched(),
                'times_returned_results' => $record->getTimesReturnedResults(),
                'times_empty' => $record->getTimesEmpty(),
            ];
        }

        $expected = [
            [
                'term' => 'clogs',
                'times_searched' => 3,
                'times_returned_results' => 2,
                'times_empty' => 1,
            ],
            [
                'term' => 'expert',
                'times_searched' => 1,
                'times_returned_results' => 0,
                'times_empty' => 1,
            ],
            [
                'term' => 'lamp',
                'times_searched' => 1,
                'times_returned_results' => 1,
                'times_empty' => 0,
            ],
            [
                'term' => 'led flashlight',
                'times_searched' => 1,
                'times_returned_results' => 1,
                'times_empty' => 0,
            ],
            [
                'term' => 'scrubs',
                'times_searched' => 1,
                'times_returned_results' => 1,
                'times_empty' => 0,
            ],
            [
                'term' => 'tires',
                'times_searched' => 1,
                'times_returned_results' => 0,
                'times_empty' => 1,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }
}
