<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;

class RestrictContentVariantByEntityEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $entity = new \stdClass();
        $alias = 'test';

        $event = new RestrictContentVariantByEntityEvent($qb, $entity, $alias);
        $this->assertSame($qb, $event->getQueryBuilder());
        $this->assertSame($entity, $event->getEntity());
        $this->assertSame($alias, $event->getVariantAlias());
    }
}
