<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;

class RestrictProductVariantEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQueryBuilder()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $event = new RestrictProductVariantEvent($queryBuilder);
        $this->assertSame($queryBuilder, $event->getQueryBuilder());
    }
}
