<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage;

class Message
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_INFO = 'info';
    const STATUS_WARNING = 'warning';

    /**
     * Translated message text.
     *
     * @var string
     */
    protected $message;

    /**
     * Message status name.
     *
     * Represents message status: success, error, etc.
     *
     * @var string
     */
    protected $status;

    /**
     * Message channel
     *
     * @var string
     */
    protected $channel;

    /**
     * Message topic
     *
     * @var string
     */
    protected $topic;

    /**
     * Full Qualified Class Name of receiver entity (Optional).
     *
     * Contain class name of entity for which message is created.
     *
     * @var string
     */
    protected $receiverEntityFQCN;

    /**
     * Receiver Entity ID (Optional).
     *
     * Contain ID value of entity for which message is created.
     *
     * @var string
     */
    protected $receiverEntityId;

    /**
     * @param string $channel
     * @param string $topic
     * @param string $message
     * @param string $status
     * @param null|string $receiverEntityFQCN
     * @param null|string $receiverEntityId
     */
    public function __construct(
        $channel,
        $topic,
        $message,
        $status,
        $receiverEntityFQCN = null,
        $receiverEntityId = null
    ) {
        $this->channel = $channel;
        $this->topic = $topic;
        $this->message = $message;
        $this->status = $status;
        $this->receiverEntityFQCN = $receiverEntityFQCN;
        $this->receiverEntityId = $receiverEntityId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getReceiverEntityFQCN()
    {
        return $this->receiverEntityFQCN;
    }

    /**
     * @return string
     */
    public function getReceiverEntityId()
    {
        return $this->receiverEntityId;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }
}
