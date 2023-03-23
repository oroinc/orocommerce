<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\SearchResult\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchResultHistoryTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 'abc'],
            ['searchTerm', 'Search  text '],
            ['normalizedSearchTermHash', md5('search text')],
            ['resultType', 'product'],
            ['resultsCount', 110],
            ['website', new Website()],
            ['localization', new Localization()],
            ['createdAt', $now, false],
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['customerVisitorId', 100],
            ['searchSession', 'abc111']
        ];

        $entity = new SearchResultHistory();
        $this->assertPropertyAccessors($entity, $properties);
    }
}
