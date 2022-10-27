<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Symfony\Component\HttpFoundation\ParameterBag;

class ProductDBQueryRestrictionEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $dataParameters = new ParameterBag(['test']);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $event = new ProductDBQueryRestrictionEvent($queryBuilder, $dataParameters);

        $this->assertSame($dataParameters, $event->getDataParameters());
        $this->assertSame($queryBuilder, $event->getQueryBuilder());
    }
}
