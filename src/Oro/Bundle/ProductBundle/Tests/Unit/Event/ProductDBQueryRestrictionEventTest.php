<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

class ProductDBQueryRestrictionEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $dataParameters = new ParameterBag(['test']);

        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder */
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ProductDBQueryRestrictionEvent($queryBuilder, $dataParameters);

        $this->assertSame($dataParameters, $event->getDataParameters());
        $this->assertSame($queryBuilder, $event->getQueryBuilder());
    }
}
