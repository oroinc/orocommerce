<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;

class RestrictProductVariantEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetQueryBuilder()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $event = new RestrictProductVariantEvent($queryBuilder);
        $this->assertSame($queryBuilder, $event->getQueryBuilder());
    }
}
