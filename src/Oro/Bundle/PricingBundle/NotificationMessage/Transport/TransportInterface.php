<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Transport;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;

interface TransportInterface
{
    /**
     * @param Message $message
     */
    public function send(Message $message);

    /**
     * @param string $channel
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     * @param null|string $topic
     * @return Message[]
     */
    public function receive($channel, $receiverEntityFQCN = null, $receiverEntityId = null, $topic = null);

    /**
     * @param string $channel
     * @param string|null $topic
     * @param null|string $receiverEntityFQCN
     * @param null|int $receiverEntityId
     */
    public function remove($channel, $topic = null, $receiverEntityFQCN = null, $receiverEntityId = null);
}
