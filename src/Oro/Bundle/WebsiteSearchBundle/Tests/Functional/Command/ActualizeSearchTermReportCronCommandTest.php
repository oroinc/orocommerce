<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchResultHistoryRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\Repository\SearchTermReportRepository;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryMultiBusinessUnitData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryOldData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart1Data;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchResultHistoryPart2Data;

class ActualizeSearchTermReportCronCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadSearchResultHistoryOldData::class,
            LoadSearchResultHistoryPart1Data::class,
            LoadSearchResultHistoryPart2Data::class,
            LoadSearchResultHistoryMultiBusinessUnitData::class,
        ]);
    }

    public function testCommand()
    {
        /** @var SearchTermReportRepository $reportRepo */
        $reportRepo = self::getContainer()->get('doctrine')->getRepository(SearchTermReport::class);
        /** @var SearchResultHistoryRepository $historyRepo */
        $historyRepo = self::getContainer()->get('doctrine')->getRepository(SearchResultHistory::class);

        $this->assertEquals(0, $reportRepo->count([]));
        $this->assertEquals(11, $historyRepo->count([]));

        $this->runCommand('oro:cron:website-search:actualize-search-term-report');

        $this->assertEquals(8, $reportRepo->count([]));
        $this->assertEquals(7, $historyRepo->count([]));
    }
}
