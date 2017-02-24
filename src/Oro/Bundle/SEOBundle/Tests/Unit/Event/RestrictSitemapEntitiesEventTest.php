<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Component\Website\WebsiteInterface;

class RestrictSitemapEntitiesEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $qb;

    /**
     * @var RestrictSitemapEntitiesEvent
     */
    protected $restrictSitemapEntitiesEvent;

    public function testEventWithWebsite()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $event = new RestrictSitemapEntitiesEvent($qb, $website);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = new RestrictSitemapEntitiesEvent($qb);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertNull($event->getWebsite());
    }
}
