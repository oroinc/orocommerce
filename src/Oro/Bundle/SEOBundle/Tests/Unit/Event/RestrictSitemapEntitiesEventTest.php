<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Contracts\EventDispatcher\Event;

class RestrictSitemapEntitiesEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEventWithWebsite()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $version = 1;

        $website = $this->createMock(WebsiteInterface::class);

        $event = new RestrictSitemapEntitiesEvent($qb, $version, $website);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertEquals($version, $event->getVersion());
        $this->assertEquals($website, $event->getWebsite());
    }

    public function testEventWithoutWebsite()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $version = 1;

        $event = new RestrictSitemapEntitiesEvent($qb, $version);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($qb, $event->getQueryBuilder());
        $this->assertEquals($version, $event->getVersion());
        $this->assertNull($event->getWebsite());
    }
}
