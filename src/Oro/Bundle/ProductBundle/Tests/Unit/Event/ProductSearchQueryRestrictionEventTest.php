<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class ProductSearchQueryRestrictionEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetQuery()
    {
        /**
         * @var SearchQueryInterface $query
         */
        $query = $this->getMock(SearchQueryInterface::class);

        $event = new ProductSearchQueryRestrictionEvent($query);

        $this->assertEquals($query, $event->getQuery());
    }
}
