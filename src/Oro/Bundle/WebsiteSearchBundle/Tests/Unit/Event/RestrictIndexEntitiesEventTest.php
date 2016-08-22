<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;

class RestrictIndexEntitiesEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var string */
    protected $entityClass;

    /** @var array */
    protected $context;

    /** @var RestrictIndexEntitiesEvent */
    protected $event;

    protected function setUp()
    {
        $this->qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->entityClass = 'Some\Class\Name';
        $this->context = ['website_id' => 1];
        $this->event = new RestrictIndexEntitiesEvent($this->qb, $this->entityClass, $this->context);
    }

    public function testQueryBuilderAccessors()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock(EntityManagerInterface::class);
        $qb = new QueryBuilder($em);
        $this->event->setQueryBuilder($qb);
        $this->assertSame($qb, $this->event->getQueryBuilder());
    }

    public function testGetEntityClass()
    {
        $this->assertEquals($this->entityClass, $this->event->getEntityClass());
    }

    public function testGetContext()
    {
        $this->assertEquals($this->context, $this->event->getContext());
    }
}
