<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProductSearchRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

class ProductSearchRestrictionEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuery()
    {
        /**
         * @var SearchQuery $query
         */
        $query = $this->getMock(SearchQuery::class);

        $event = new ProductSearchRestrictionEvent($query);

        $this->assertEquals($query, $event->getQuery());
    }
}
