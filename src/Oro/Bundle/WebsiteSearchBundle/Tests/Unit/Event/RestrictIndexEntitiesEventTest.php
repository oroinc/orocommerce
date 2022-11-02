<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictIndexEntitiesEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $qb;

    /** @var string */
    protected $entityClass;

    /** @var array */
    protected $context;

    /** @var RestrictIndexEntityEvent */
    protected $event;

    protected function setUp(): void
    {
        $this->qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
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
