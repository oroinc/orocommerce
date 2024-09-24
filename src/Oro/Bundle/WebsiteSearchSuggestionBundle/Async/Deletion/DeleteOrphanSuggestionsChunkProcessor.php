<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The processor obtains the chunk data of suggestion ids and removes the entities.
 */
class DeleteOrphanSuggestionsChunkProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EventDispatcher $eventDispatcher
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $suggestionIds = $body[DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS];

        $this->getSuggestionRepository()->removeSuggestionsByIds($suggestionIds);

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

    private function getSuggestionRepository(): SuggestionRepository
    {
        return $this->doctrine->getRepository(Suggestion::class);
    }
}
