<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;

/**
 * @ORM\Table(
 *      name="oro_notification_message",
 *      indexes={
 *          @ORM\Index(
 *              name="oro_notif_msg_channel",
 *              columns={"channel", "topic"}
 *          ),
 *          @ORM\Index(
 *              name="oro_notif_msg_entity",
 *              columns={"receiver_entity_fqcn", "receiver_entity_id"}
 *          ),
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\NotificationMessageRepository")
 */
class NotificationMessage implements CreatedAtAwareInterface
{
    use CreatedAtAwareTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Translated message text.
     *
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    protected $message;

    /**
     * Message status name.
     *
     * Represents message status: success, error, etc.
     *
     * @var string
     *
     * @ORM\Column(name="message_status", type="string", length=255, nullable=false)
     */
    protected $messageStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="channel", type="string", length=255, nullable=false)
     */
    protected $channel;

    /**
     * @var string
     *
     * @ORM\Column(name="topic", type="string", length=255, nullable=false)
     */
    protected $topic;

    /**
     * Full Qualified Class Name of receiver entity (Optional).
     *
     * Contain class name of entity for which message is created.
     *
     * @var string
     *
     * @ORM\Column(name="receiver_entity_fqcn", type="string", length=255, nullable=true)
     */
    protected $receiverEntityFQCN;

    /**
     * Receiver Entity ID (Optional).
     *
     * Contain ID value of entity for which message is created.
     *
     * @var string
     *
     * @ORM\Column(name="receiver_entity_id", type="integer", nullable=true)
     */
    protected $receiverEntityId;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_resolved", type="boolean")
     */
    protected $resolved = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="resolved_at", type="datetime", nullable=true)
     */
    protected $resolvedAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageStatus()
    {
        return $this->messageStatus;
    }

    /**
     * @param string $messageStatus
     * @return $this
     */
    public function setMessageStatus($messageStatus)
    {
        $this->messageStatus = $messageStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverEntityFQCN()
    {
        return $this->receiverEntityFQCN;
    }

    /**
     * @param string $receiverEntityFQCN
     * @return $this
     */
    public function setReceiverEntityFQCN($receiverEntityFQCN)
    {
        $this->receiverEntityFQCN = $receiverEntityFQCN;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverEntityId()
    {
        return $this->receiverEntityId;
    }

    /**
     * @param string $receiverEntityId
     * @return $this
     */
    public function setReceiverEntityId($receiverEntityId)
    {
        $this->receiverEntityId = $receiverEntityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * @param boolean $resolved
     * @return $this
     */
    public function setResolved($resolved)
    {
        $this->resolved = $resolved;
        if ($resolved) {
            $this->setResolvedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        } else {
            $this->setResolvedAt(null);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getResolvedAt()
    {
        return $this->resolvedAt;
    }

    /**
     * @param \DateTime $resolvedAt
     * @return $this
     */
    public function setResolvedAt(\DateTime $resolvedAt)
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    /**
     * @param string $topic
     * @return $this
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }
}
