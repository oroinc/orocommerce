<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage\Event;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvent;

class MessageEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Message|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $message;

    /**
     * @var MessageEvent
     */
    protected $messageEvent;

    protected function setUp()
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
        /** @var Message|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->messageEvent->setMessage($message);
        $this->assertSame($message, $this->messageEvent->getMessage());
    }
}
