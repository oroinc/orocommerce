<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Component\Website\WebsiteInterface;

class RestrictSitemapEntitiesEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RestrictSitemapEntitiesEvent
     */
    protected $restrictSitemapEntitiesEvent;

    public function testEventWithWebsite()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $version = 1;

        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);

        $event = new RestrictSitemapEntitiesEvent($qb, $version, $website);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertEquals($version, $event->getVersion());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $version = 1;

        $event = new RestrictSitemapEntitiesEvent($qb, $version);

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertEquals($version, $event->getVersion());
        $this->assertNull($event->getWebsite());
    }
}
