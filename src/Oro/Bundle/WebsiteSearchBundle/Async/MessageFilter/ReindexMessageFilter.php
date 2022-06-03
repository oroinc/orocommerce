<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async\MessageFilter;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Component\MessageQueue\Client\Message;

/**
 * Aggregates multiple reindex messages.
 */
class ReindexMessageFilter implements MessageFilterInterface
{
    private const NO_GROUPS_KEY = '_';

    use ContextTrait;

    private string $topic;

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
     * @param Message|array|string $message
     *
     * @return array
     */
    private function getMessageData($message): array
    {
        if (is_string($message)) {
            return [];
        }

        return $message instanceof Message ? $message->getBody() : $message;
    }

    /**
     * Aggregates messages from the same topic into one message.
     */
    private function aggregateMessages(MessageBuffer $buffer): void
    {
        [
            $firstMessages,
            $messageKeysForFullReindex,
            $messageKeysForFullGroupReindex,
            $entityIdsByGroupsByMessageKey
        ] = $this->getPreparedIndexationInformation($buffer);

        $this->scheduleFullIndexation($buffer, $messageKeysForFullReindex, $firstMessages);
        $this->scheduleFullIndexationByFieldGroups($buffer, $messageKeysForFullGroupReindex, $firstMessages);
        $this->scheduleIndexationPerEntities($buffer, $entityIdsByGroupsByMessageKey, $firstMessages);
    }

    private function getMessageKey(array $messageData): string
    {
        if (!empty($messageData['jobId'])) {
            return $messageData['jobId'];
        }

        return sprintf(
            '%s|%s|%s|%s',
            $messageData['jobId'] ?? '',
            implode(',', (array)($messageData['class'] ?? [])),
            implode(',', (array)($messageData['context']['websiteIds'] ?? [])),
            $messageData['granulize'] ?? false
        );
    }

    private function getMergedFieldGroups(?array $fieldGroups): ?array
    {
        // If full indexation was requested (there is a message without field groups passed) run full reindexation
        // and skip field groups merging
        if ($fieldGroups
            && !in_array(null, $fieldGroups, true)
            && !in_array([], $fieldGroups, true)
        ) {
            $mergedGroups = array_values(array_unique(array_merge(...$fieldGroups)));
            sort($mergedGroups);

            return $mergedGroups;
        }

        return null;
    }

    /**
     * Returns first messages, indexation information oer each entity and information about requested full reindex.
     */
    private function getPreparedIndexationInformation(MessageBuffer $buffer): array
    {
        $firstMessages = [];
        $entityIdsByMessageKey = [];
        $messageKeysForFullReindex = [];
        $messageKeyFullGroupReindex = [];
        $messages = $buffer->getMessagesForTopic($this->topic);

        foreach ($messages as $messageId => $message) {
            $messageData = $this->getMessageData($message);
            $messageKey = $this->getMessageKey($messageData);

            if (!isset($firstMessages[$messageKey])) {
                $firstMessages[$messageKey] = $message;
            }
            $buffer->removeMessage($messageId);

            $entityIds = $this->getContextEntityIds($messageData['context'] ?? []);
            $fieldGroups = $this->getContextFieldGroups($messageData['context'] ?? []) ?? [];

            if (!empty($messageKeysForFullReindex[$messageKey])) {
                continue;
            }

            $this->fillFieldGroupsPerEntityForMessage(
                $messageKey,
                $entityIds,
                $fieldGroups,
                $messageKeyFullGroupReindex,
                $entityIdsByMessageKey
            );
            if (!$entityIds) {
                $this->fillFullReindexationInfo(
                    $messageKey,
                    $fieldGroups,
                    $messageKeysForFullReindex,
                    $entityIdsByMessageKey,
                    $messageKeyFullGroupReindex
                );
            }
        }

        return [
            $firstMessages,
            $messageKeysForFullReindex,
            $messageKeyFullGroupReindex,
            $this->getEntityIdsPerMessageGroups($entityIdsByMessageKey, $messageKeyFullGroupReindex)
        ];
    }

    /**
     * Fill information about indexation field groups requested per each individual entity.
     */
    private function fillFieldGroupsPerEntityForMessage(
        string $messageKey,
        array $entityIds,
        array $fieldGroups,
        array $messageKeyFullGroupReindex,
        array &$entityIdsByMessageKey
    ): void {
        foreach ($entityIds as $entityId) {
            if ($fieldGroups) {
                $fieldGroups = array_diff($fieldGroups, $messageKeyFullGroupReindex[$messageKey] ?? []);
                if (!$fieldGroups) {
                    continue;
                }
            }
            $entityIdsByMessageKey[$messageKey][$entityId][] = $fieldGroups;
        }
    }

    /**
     * Fill information about requested FULL indexation/FULL indexation per some fields group.
     * When FULL indexation is requested - remove all other indexation requests that were already scheduled,
     * all further indexation requests will be skipped if there is FULL indexation requested.
     */
    private function fillFullReindexationInfo(
        string $messageKey,
        array $fieldGroups,
        array &$messageKeysForFullReindex,
        array &$entityIdsByMessageKey,
        array &$messageKeyFullGroupReindex
    ): void {
        if (!$fieldGroups) {
            $messageKeysForFullReindex[$messageKey] = true;
            unset($entityIdsByMessageKey[$messageKey], $messageKeyFullGroupReindex[$messageKey]);
        } else {
            $messageKeyFullGroupReindex[$messageKey] = array_merge(
                $messageKeyFullGroupReindex[$messageKey] ?? [],
                $fieldGroups
            );
        }
    }

    private function scheduleFullIndexation(
        MessageBuffer $buffer,
        array $messageKeysForFullReindex,
        array $firstMessages
    ): void {
        $processMessageBody = static function (array $body) {
            unset($body['context']['entityIds'], $body['context']['fieldGroups']);
            if (empty($body['context'])) {
                unset($body['context']);
            }

            return $body;
        };

        foreach (array_keys($messageKeysForFullReindex) as $messageKey) {
            $message = $firstMessages[$messageKey];
            if ($message instanceof Message) {
                $message->setBody($processMessageBody($message->getBody()));
            } else {
                $message = $processMessageBody($message);
            }

            $buffer->addMessage($this->topic, $message);
        }
    }

    private function scheduleFullIndexationByFieldGroups(
        MessageBuffer $buffer,
        array $messageKeyFullGroupReindex,
        array $firstMessages
    ): void {
        $processMessageBody = static function (array $body, ?array $fieldGroups) {
            $body['context']['fieldGroups'] = $fieldGroups;
            unset($body['context']['entityIds']);

            return $body;
        };

        foreach ($messageKeyFullGroupReindex as $messageKey => $fieldGroups) {
            $message = $firstMessages[$messageKey];
            if ($message instanceof Message) {
                $message->setBody($processMessageBody($message->getBody(), $fieldGroups));
            } else {
                $message = $processMessageBody($message, $fieldGroups);
            }

            $buffer->addMessage($this->topic, $message);
        }
    }

    private function getEntityIdsPerMessageGroups(array $entityIdsByMessageKey, $messageKeysForFullGroupReindex): array
    {
        $entityIdsByGroupsByMessageKey = [];
        foreach ($entityIdsByMessageKey as $messageKey => $entityIdGroups) {
            foreach ($entityIdGroups as $entityId => $fieldGroups) {
                $mergedGroups = $this->getMergedFieldGroups($fieldGroups);
                if ($mergedGroups !== null) {
                    $mergedGroups = array_diff($mergedGroups, $messageKeysForFullGroupReindex[$messageKey] ?? []);
                    if (!$mergedGroups) {
                        continue;
                    }
                }
                $key = $this->getKeyByFieldGroups($mergedGroups);
                $entityIdsByGroupsByMessageKey[$messageKey][$key][] = $entityId;
            }
        }

        return $entityIdsByGroupsByMessageKey;
    }

    private function scheduleIndexationPerEntities(
        MessageBuffer $buffer,
        array $entityIdsByGroupsByMessageKey,
        array $firstMessages
    ): void {
        $processMessageBody = static function (array $body, array $entityIds, ?array $fieldGroups) {
            $body['context']['entityIds'] = $entityIds;
            if ($fieldGroups) {
                $body['context']['fieldGroups'] = $fieldGroups;
            } else {
                unset($body['context']['fieldGroups']);
            }

            return $body;
        };

        foreach ($entityIdsByGroupsByMessageKey as $messageKey => $groupedIds) {
            foreach ($groupedIds as $groups => $entityIds) {
                $fieldGroups = $this->getFieldGroupsFromKey($groups);
                if ($firstMessages[$messageKey] instanceof Message) {
                    // Apply changes to a clone, because changing same message in a loop will change it in a buffer.
                    $message = clone $firstMessages[$messageKey];
                    $message->setBody($processMessageBody($message->getBody(), $entityIds, $fieldGroups));
                } else {
                    $message = $firstMessages[$messageKey];
                    $message = $processMessageBody($message, $entityIds, $fieldGroups);
                }

                $buffer->addMessage($this->topic, $message);
            }
        }
    }

    private function getKeyByFieldGroups(?array $mergedGroups): string
    {
        return $mergedGroups === null ? self::NO_GROUPS_KEY : implode(',', $mergedGroups);
    }

    private function getFieldGroupsFromKey(string $groups): ?array
    {
        if ($groups === self::NO_GROUPS_KEY) {
            $fieldGroups = null;
        } else {
            $fieldGroups = explode(',', $groups);
        }

        return $fieldGroups;
    }
}
