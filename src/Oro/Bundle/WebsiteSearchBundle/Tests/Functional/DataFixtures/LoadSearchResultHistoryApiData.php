<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

class LoadSearchResultHistoryApiData extends LoadSearchResultHistoryData
{
    protected function getCsvPath(): string
    {
        return __DIR__ . '/data/search_result_history_api.csv';
    }
}
