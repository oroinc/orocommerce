<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Removes duplicated messages for a specific category visibility management related topic.
 */
class CategoryMessageFilter implements MessageFilterInterface
{
    /** @var string */
    private $topic;

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
            $messageKey = isset($message['id']) ? (string)$message['id'] : '_';
            if (isset($processedMessages[$messageKey])) {
                $buffer->removeMessage($messageId);
            } else {
                $processedMessages[$messageKey] = true;
            }
        }
    }
}
