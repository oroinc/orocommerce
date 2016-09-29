<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage;

use Oro\Bundle\PricingBundle\NotificationMessage\Event\MassMessagesEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvents;
use Oro\Bundle\PricingBundle\NotificationMessage\Transport\TransportInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Messenger
{
    /**
     * @var TransportInterface[]
     */
    protected $transports = [];

    /**
     * @var TransportInterface
     */
    protected $sender;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param TransportInterface $sender
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        TransportInterface $sender,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->sender = $sender;
        $this->addTransport($sender);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param TransportInterface $transport
     */
    public function addTransport(TransportInterface $transport)
    {
        if (!in_array($transport, $this->transports, true)) {
            $this->transports[] = $transport;
        }
    }

    /**
     * @param string $channel
     * @param string $topic
     * @param null|string $status
     * @param string $messageText
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     */
    public function send(
        $channel,
        $topic,
        $status,
        $messageText,
        $receiverEntityFQCN = null,
        $receiverEntityId = null
    ) {
        $message = new Message($channel, $topic, $messageText, $status, $receiverEntityFQCN, $receiverEntityId);

        $messageEvent = new MessageEvent($message);
        $this->eventDispatcher->dispatch(MessageEvents::BEFORE_SEND, $messageEvent);
        $this->sender->send($messageEvent->getMessage());
        $this->eventDispatcher->dispatch(MessageEvents::AFTER_SEND, $messageEvent);
    }

    /**
     * @param string $channel
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     * @param null|string $topic
     * @return Message[]
     */
    public function receive($channel, $receiverEntityFQCN = null, $receiverEntityId = null, $topic = null)
    {
        $messages = [];
        foreach ($this->transports as $transport) {
            // Second foreach added to support generators in transport
            foreach ($transport->receive($channel, $receiverEntityFQCN, $receiverEntityId, $topic) as $message) {
                $messages[] = $message;
            }
        }
        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic, $messages);
        $this->eventDispatcher->dispatch(MessageEvents::ON_RECEIVE, $event);

        return $event->getMessages();
    }

    /**
     * @param string $channel
     * @param string $topic
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     */
    public function remove($channel, $topic, $receiverEntityFQCN = null, $receiverEntityId = null)
    {
        foreach ($this->transports as $transport) {
            $transport->remove($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
        }
        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic);
        $this->eventDispatcher->dispatch(MessageEvents::ON_REMOVE, $event);
    }
}
