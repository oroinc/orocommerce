<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexRequestListener;

class ReindexRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASSNAME = 'testClass';
    const TEST_WEBSITE_ID = 1234;

    /**
     * @var ReindexRequestListener
     */
    protected $listener;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $regularIndexerMock;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $asyncIndexerMock;

    public function setUp()
    {
        $this->regularIndexerMock = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->asyncIndexerMock   = $this->getMockBuilder(IndexerInterface::class)->getMock();

        $this->listener = new ReindexRequestListener(
            $this->regularIndexerMock,
            $this->asyncIndexerMock
        );
    }

    public function testProcessWithoutIndexers()
    {
        $event = $this->getReindexationRequestEvent();

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('reindex');

        (new ReindexRequestListener())->process($event);
    }

    public function testProcess()
    {
        $event = $this->getReindexationRequestEvent();

        $this->regularIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with([self::TEST_CLASSNAME]);

        $this->asyncIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    public function testProcessAsync()
    {
        $event = new ReindexationRequestEvent(
            [self::TEST_CLASSNAME],
            [self::TEST_WEBSITE_ID],
            [],
            true
        );

        $this->asyncIndexerMock
            ->expects($this->once())
            ->method('reindex')
            ->with([self::TEST_CLASSNAME]);

        $this->regularIndexerMock
            ->expects($this->never())
            ->method('reindex');

        $this->listener->process($event);
    }

    /**
     * @return ReindexationRequestEvent
     */
    private function getReindexationRequestEvent()
    {
        $event = new ReindexationRequestEvent(
            [self::TEST_CLASSNAME],
            [self::TEST_WEBSITE_ID],
            [],
            false
        );

        return $event;
    }
}
