<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The processor obtains the chunk data of suggestion ids and removes the entities.
 */
class DeleteOrphanSuggestionsChunkProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        $suggestionIds = $body[DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS];

        $this->doctrine->getRepository(Suggestion::class)->removeSuggestionsByIds($suggestionIds);

        $event = new SuggestionDeleteEvent();
        $event->setDeletedSuggestionIds($suggestionIds);

        $this->eventDispatcher->dispatch($event);

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [DeleteOrphanSuggestionsChunkTopic::getName()];
    }
}
