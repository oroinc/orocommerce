<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class BuildQueryProductListEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        $productListType = 'test_list';
        $query = $this->createMock(SearchQueryInterface::class);

        $event = new BuildQueryProductListEvent($productListType, $query);

        self::assertSame($productListType, $event->getProductListType());
        self::assertSame($query, $event->getQuery());
    }
}
