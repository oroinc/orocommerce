<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Removes duplicated messages for a specific visibility management related topic.
 */
class VisibilityMessageFilter implements MessageFilterInterface
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
            $messageKey = $this->getMessageKey($message);
            if (isset($processedMessages[$messageKey])) {
                $buffer->removeMessage($messageId);
            } else {
                $processedMessages[$messageKey] = true;
            }
        }
    }

    private function getMessageKey(array $message): string
    {
        if (isset($message['id'])) {
            return $message['entity_class_name'] . ':' . $message['id'];
        }

        return
            $message['entity_class_name']
            . ':'
            . $message['target_class_name']
            . ':'
            . $message['target_id']
            . ':'
            . $message['scope_id'];
    }
}
