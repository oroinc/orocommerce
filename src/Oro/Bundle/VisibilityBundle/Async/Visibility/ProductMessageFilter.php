<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Removes duplicated messages for a specific product visibility management related topic.
 * Aggregates messages into single message.
 */
class ProductMessageFilter implements MessageFilterInterface
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

        $this->aggregateMessages($buffer);
    }

    /**
     * Aggregates messages from the same topic into one message.
     */
    private function aggregateMessages(MessageBuffer $buffer): void
    {
        $productIds = [];
        $firstMessageId = null;
        $messages = $buffer->getMessagesForTopic($this->topic);
        foreach ($messages as $messageId => $message) {
            if ($firstMessageId === null) {
                $firstMessageId = $messageId;
            } else {
                $buffer->removeMessage($messageId);
            }

            $productIds[$message['id']] = $message['id'];
        }

        if ($firstMessageId !== null) {
            /** @var array $firstMessage */
            $firstMessage = $buffer->getMessage($firstMessageId);

            // Schedules reindex if number of products for visibility resolving is greater than 1.
            if (count($productIds) > 1) {
                $firstMessage['id'] = array_values($productIds);
                $firstMessage['scheduleReindex'] = true;
            } else {
                $firstMessage['id'] = reset($productIds);
            }

            $buffer->replaceMessage($firstMessageId, $firstMessage);
        }
    }
}
