<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testMessageRequiredFields()
    {
        $message = new Message('channel', 'topic', 'message', 'status');
        $this->assertEquals('channel', $message->getChannel());
        $this->assertEquals('topic', $message->getTopic());
        $this->assertEquals('message', $message->getMessage());
        $this->assertEquals('status', $message->getStatus());
    }

    public function testMessageForAllEntities()
    {
        $message = new Message('channel', 'topic', 'message', 'status', 'EntityFQCN');
        $this->assertEquals('channel', $message->getChannel());
        $this->assertEquals('topic', $message->getTopic());
        $this->assertEquals('message', $message->getMessage());
        $this->assertEquals('status', $message->getStatus());
        $this->assertEquals('EntityFQCN', $message->getReceiverEntityFQCN());
    }

    public function testMessageForSpecificEntity()
    {
        $message = new Message('channel', 'topic', 'message', 'status', 'EntityFQCN', 4);
        $this->assertEquals('channel', $message->getChannel());
        $this->assertEquals('topic', $message->getTopic());
        $this->assertEquals('message', $message->getMessage());
        $this->assertEquals('status', $message->getStatus());
        $this->assertEquals('EntityFQCN', $message->getReceiverEntityFQCN());
        $this->assertEquals(4, $message->getReceiverEntityId());
    }
}
