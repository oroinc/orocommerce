<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;

class RestrictIndexEntitiesEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var string */
    protected $entityClassname;

    /** @var array */
    protected $context;

    /** @var RestrictIndexEntitiesEvent */
    protected $event;

    protected function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->entityClassname = 'Some\Class\Name';
        $this->context = ['website_id' => 1];
        $this->event = new RestrictIndexEntitiesEvent($this->queryBuilder, $this->entityClassname, $this->context);
    }

    public function testQueryBuilderAccessors()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $emMock */
        $emMock = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $qb = new QueryBuilder($emMock);
        $this->event->setQueryBuilder($qb);
        $this->assertEquals($qb, $this->event->getQueryBuilder());
    }

    public function testGetEntityClassname()
    {
        $this->assertEquals($this->entityClassname, $this->event->getEntityClassname());
    }

    public function testGetContext()
    {
        $this->assertEquals($this->context, $this->event->getContext());
    }
}
