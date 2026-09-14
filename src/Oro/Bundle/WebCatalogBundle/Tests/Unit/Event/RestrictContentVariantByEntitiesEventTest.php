<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntitiesEvent;
use PHPUnit\Framework\TestCase;

class RestrictContentVariantByEntitiesEventTest extends TestCase
{
    public function testEvent(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $event = new RestrictContentVariantByEntitiesEvent(
            $queryBuilder,
            \stdClass::class,
            [1, 2],
            'variant',
            'ownerId'
        );

        self::assertSame($queryBuilder, $event->getQueryBuilder());
        self::assertSame(\stdClass::class, $event->getEntityClass());
        self::assertSame([1, 2], $event->getEntityIds());
        self::assertSame('variant', $event->getVariantAlias());
        self::assertSame('ownerId', $event->getOwnerIdAlias());
        self::assertFalse($event->isRestricted());

        $event->setRestricted(true);

        self::assertTrue($event->isRestricted());
    }
}
