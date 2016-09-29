<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Event;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Symfony\Component\EventDispatcher\Event;

class MassMessagesEvent extends Event
{
    /**
     * @var Message[]
     */
    private $messages;

    /**
     * @var null|string
     */
    private $channel;

    /**
     * @var null|string
     */
    private $receiverEntityFQCN;

    /**
     * @var null|int
     */
    private $receiverEntityId;

    /**
     * @var null|string
     */
    private $topic;

    /**
     * @param null|string $channel
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     * @param null|string $topic
     * @param Message[] $messages
     */
    public function __construct(
        $channel = null,
        $receiverEntityFQCN = null,
        $receiverEntityId = null,
        $topic = null,
        array $messages = null
    ) {
        $this->messages = $messages;
        $this->channel = $channel;
        $this->receiverEntityFQCN = $receiverEntityFQCN;
        $this->receiverEntityId = $receiverEntityId;
        $this->topic = $topic;
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return null|string
     */
    public function getReceiverEntityFQCN()
    {
        return $this->receiverEntityFQCN;
    }

    /**
     * @return null|string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return int|null
     */
    public function getReceiverEntityId()
    {
        return $this->receiverEntityId;
    }

    /**
     * @return null|string
     */
    public function getTopic()
    {
        return $this->topic;
    }
}
