<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;

class BeforeSearchEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BeforeSearchEvent
     */
    private $event;

    /**
     * @var Query
     */
    private $query;

    protected function setUp()
    {
        $this->query = new Query();
        $this->event = new BeforeSearchEvent($this->query, []);
    }

    protected function tearDown()
    {
        unset($this->query, $this->event);
    }

    public function testGetQuery()
    {
        $this->assertInstanceOf('Oro\Bundle\SearchBundle\Query\Query', $this->event->getQuery());
        $this->assertSame($this->query, $this->event->getQuery());
    }

    public function testGetContext()
    {
        $this->assertInternalType('array', $this->event->getContext());
        $this->assertSame([], $this->event->getContext());
    }
}
