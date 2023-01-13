<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;

class BeforeSearchEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var Query */
    private $query;

    /** @var BeforeSearchEvent */
    private $event;

    protected function setUp(): void
    {
        $this->query = new Query();
        $this->event = new BeforeSearchEvent($this->query, []);
    }

    public function testGetQuery()
    {
        $this->assertInstanceOf(Query::class, $this->event->getQuery());
        $this->assertSame($this->query, $this->event->getQuery());
    }

    public function testGetContext()
    {
        $this->assertIsArray($this->event->getContext());
        $this->assertSame([], $this->event->getContext());
    }
}
