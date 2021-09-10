<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Event;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Symfony\Contracts\EventDispatcher\Event;

class MessageEvent extends Event
{
    /**
     * @var Message
     */
    private $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(Message $message)
    {
        $this->message = $message;
    }
}
