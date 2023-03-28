<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

class LoadSearchResultHistoryOldData extends AbstractLoadSearchResultHistoryData
{
    protected function getCsvPath(): string
    {
        return __DIR__ . '/data/search_result_history_old.csv';
    }
}
