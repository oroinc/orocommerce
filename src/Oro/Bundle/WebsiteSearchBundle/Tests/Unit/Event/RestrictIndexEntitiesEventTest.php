<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictIndexEntitiesEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $qb;

    /** @var array */
    private $context;

    /** @var RestrictIndexEntityEvent */
    private $event;

    protected function setUp(): void
    {
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->context = ['website_id' => 1];
        $this->event = new RestrictIndexEntityEvent($this->qb, $this->context);
    }

    public function testQueryBuilderAccessors()
    {
        $this->assertSame($this->qb, $this->event->getQueryBuilder());
    }

    public function testGetContext()
    {
        $this->assertEquals($this->context, $this->event->getContext());
    }
}
