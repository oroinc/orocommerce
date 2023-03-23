<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;
use PHPUnit\Framework\MockObject\MockObject;

class AfterSearchEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var Query */
    private $query;

    /** @var Result|MockObject */
    private $result;

    /** @var AfterSearchEvent */
    private $event;

    protected function setUp(): void
    {
        $this->query = new Query();
        $this->result = $this->createMock(Result::class);
        $this->event = new AfterSearchEvent($this->result, $this->query, []);
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

    public function testGetResult()
    {
        $this->assertSame($this->result, $this->event->getResult());
    }
}
