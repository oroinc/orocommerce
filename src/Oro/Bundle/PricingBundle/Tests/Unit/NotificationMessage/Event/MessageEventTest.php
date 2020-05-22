<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage\Event;

use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;

class MessageEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Message|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $message;

    /**
     * @var MessageEvent
     */
    protected $messageEvent;

    protected function setUp(): void
    {
        $this->message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageEvent = new MessageEvent($this->message);
    }

    public function testGetMessage()
    {
        $this->assertSame($this->message, $this->messageEvent->getMessage());
    }

    public function testSetMessage()
    {
        /** @var Message|\PHPUnit\Framework\MockObject\MockObject $message **/
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageEvent->setMessage($message);
        $this->assertSame($message, $this->messageEvent->getMessage());
    }
}
