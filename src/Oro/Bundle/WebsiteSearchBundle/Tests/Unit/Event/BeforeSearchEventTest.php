<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;

class BeforeSearchEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BeforeSearchEvent
     */
    private $event;

    /**
     * @var Query
     */
    private $query;

    protected function setUp(): void
    {
        $this->query = new Query();
        $this->event = new BeforeSearchEvent($this->query, []);
    }

    protected function tearDown(): void
    {
        unset($this->query, $this->event);
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
