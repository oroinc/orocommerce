<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictIndexEntitiesEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var string */
    protected $entityClass;

    /** @var array */
    protected $context;

    /** @var RestrictIndexEntityEvent */
    protected $event;

    protected function setUp()
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
