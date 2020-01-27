<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Event\RestrictSlugIncrementEvent;

class RestrictSlugIncrementEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent(): void
    {
        /** @var SluggableInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(SluggableInterface::class);
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $event = new RestrictSlugIncrementEvent($queryBuilder, $entity);

        $this->assertSame($entity, $event->getEntity());
        $this->assertSame($queryBuilder, $event->getQueryBuilder());
    }
}
