<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchResult\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryOldData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart1Data;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart2Data;

/**
 * @dbIsolationPerTest
 */
class SearchTermReportRepositoryTest extends WebTestCase
{
    private SearchTermReportRepository $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->repo = self::getContainer()->get('doctrine')->getRepository(SearchTermReport::class);
    }

    /**
     * @dataProvider upsertDataProvider
     */
    public function testUpsertSearchHistoryRecord(\DateTimeZone $timeZone, array $expected)
    {
        $this->loadFixtures([
            LoadSearchResultHistoryOldData::class,
            LoadSearchResultHistoryPart1Data::class,
            LoadSearchResultHistoryPart2Data::class,
        ]);

        /** @var SearchResultHistory $firstRecord */
        $firstRecord = $this->getReference('search_result_tires');

        $this->repo->actualizeReport($firstRecord->getOrganization(), $timeZone);

        $actual = [];
        foreach ($this->repo->findBy([], ['searchTerm' => 'ASC', 'searchDate' => 'ASC']) as $record) {
            $actual[] = [
                'term' => $record->getSearchTerm(),
                'times_searched' => $record->getTimesSearched(),
                'times_returned_results' => $record->getTimesReturnedResults(),
                'times_empty' => $record->getTimesEmpty(),
                'searched_at' => $record->getSearchDate()->format('Y-m-d'),
            ];
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider upsertDataProvider
     */
    public function testUpsertSearchHistoryRecordIncrementalLoad(\DateTimeZone $timeZone, array $expected)
    {
        $this->loadFixtures([LoadSearchResultHistoryOldData::class]);

        /** @var Organization $organization */
        $organization = $this->getReference('search_result_scrubs')->getOrganization();

        $this->repo->actualizeReport($organization, $timeZone);

        $this->loadFixtures([LoadSearchResultHistoryPart1Data::class]);
        $this->repo->actualizeReport($organization, $timeZone);

        $this->loadFixtures([LoadSearchResultHistoryPart2Data::class]);
        $this->repo->actualizeReport($organization, $timeZone);

        $actual = [];
        foreach ($this->repo->findBy([], ['searchTerm' => 'ASC', 'searchDate' => 'ASC']) as $record) {
            $actual[] = [
                'term' => $record->getSearchTerm(),
                'times_searched' => $record->getTimesSearched(),
                'times_returned_results' => $record->getTimesReturnedResults(),
                'times_empty' => $record->getTimesEmpty(),
                'searched_at' => $record->getSearchDate()->format('Y-m-d'),
            ];
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upsertDataProvider(): \Generator
    {
        yield 'UTC' => [
            new \DateTimeZone('UTC'),
            [
                [
                    'term' => 'clogs',
                    'times_searched' => 3,
                    'times_returned_results' => 2,
                    'times_empty' => 1,
                    'searched_at' => (new \DateTime('-31 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'expert',
                    'times_searched' => 1,
                    'times_returned_results' => 0,
                    'times_empty' => 1,
                    'searched_at' => (new \DateTime('-2 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'lamp',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'led flashlight',
                    'times_searched' => 2,
                    'times_returned_results' => 2,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('today', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'scrubs',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('-33 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'tires',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
            ],
        ];

        yield 'Pacific/Tahiti' => [
            new \DateTimeZone('Pacific/Tahiti'),
            [
                [
                    'term' => 'clogs',
                    'times_searched' => 3,
                    'times_returned_results' => 2,
                    'times_empty' => 1,
                    'searched_at' => (new \DateTime('-31 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'expert',
                    'times_searched' => 1,
                    'times_returned_results' => 0,
                    'times_empty' => 1,
                    'searched_at' => (new \DateTime('-2 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'lamp',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'led flashlight',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'led flashlight',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('today', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'scrubs',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('-33 days', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
                [
                    'term' => 'tires',
                    'times_searched' => 1,
                    'times_returned_results' => 1,
                    'times_empty' => 0,
                    'searched_at' => (new \DateTime('yesterday', new \DateTimeZone('UTC')))->format('Y-m-d'),
                ],
            ],
        ];
    }
}
