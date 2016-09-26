<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryEvent;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductSearchQueryEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuery()
    {
        /**
         * @var Query $query
         */
        $query = $this->getMock(Query::class);

        $event = new ProductSearchQueryEvent($query);

        $this->assertEquals($query, $event->getQuery());
    }
}
