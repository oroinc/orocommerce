<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResult;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchResultTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['searchTerm', 'search text'],
            ['resultType', 'product'],
            ['result', 110],
            ['website', new Website()],
            ['localization', new Localization()],
            ['createdAt', $now, false],
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
        ];

        $entity = new SearchResult();
        $this->assertPropertyAccessors($entity, $properties);
    }
}
