<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

class LoadSearchResultHistoryPart2Data extends AbstractLoadSearchResultHistoryData
{
    protected function getCsvPath(): string
    {
        return __DIR__ . '/data/search_result_history_part2.csv';
    }
}
