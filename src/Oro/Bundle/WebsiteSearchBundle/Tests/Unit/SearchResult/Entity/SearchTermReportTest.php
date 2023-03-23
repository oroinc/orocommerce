<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\SearchResult\Entity;

use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchTermReport;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchTermReportTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 'abc'],
            ['searchTerm', 'Search  text '],
            ['normalizedSearchTermHash', md5('search text')],
            ['timesSearched', 110],
            ['timesReturnedResults', 100],
            ['timesEmpty', 10],
            ['searchDate', $now, false],
        ];

        $entity = new SearchTermReport();
        $this->assertPropertyAccessors($entity, $properties);
    }
}
