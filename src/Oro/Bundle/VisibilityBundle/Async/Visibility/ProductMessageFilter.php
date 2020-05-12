<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Removes duplicated messages for a specific product visibility management related topic.
 */
class ProductMessageFilter implements MessageFilterInterface
{
    /** @var string */
    private $topic;

    /**
     * @param string $topic
     */
    public function __construct(string $topic)
    {
        $this->topic = $topic;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic($this->topic)) {
            return;
        }

        $processedMessages = [];
        $messages = $buffer->getMessagesForTopic($this->topic);
        foreach ($messages as $messageId => $message) {
            $messageKey = (string)$message['id'];
            if (isset($processedMessages[$messageKey])) {
                $buffer->removeMessage($messageId);
            } else {
                $processedMessages[$messageKey] = true;
            }
        }
    }
}
