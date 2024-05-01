<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistProductsSuggestionRelationChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistSuggestionPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\SuggestionPersister;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processor for persisting suggestions to database
 */
class PersistSuggestionPhrasesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private MessageProducerInterface $producer,
        private SuggestionPersister $suggestionPersister,
        private int $chunkSize
    ) {
    }

    #[\Override] public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $persistedSuggestions = $this->suggestionPersister->persistSuggestions(
            $body[PersistSuggestionPhrasesChunkTopic::ORGANIZATION],
            $body[PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER]
        );

        foreach (array_chunk($persistedSuggestions, $this->chunkSize, true) as $persistedSuggestionsChunk) {
            $this->sendMessageToPersistProducts($persistedSuggestionsChunk);
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [PersistSuggestionPhrasesChunkTopic::getName()];
    }

    /**
     * @var array<int, array<int>> $persistedSuggestions
     * @return void
     */
    private function sendMessageToPersistProducts(array $persistedSuggestions): void
    {
        $this->producer->send(
            PersistProductsSuggestionRelationChunkTopic::getName(),
            [PersistProductsSuggestionRelationChunkTopic::PRODUCTS_WRAPPER => $persistedSuggestions]
        );
    }
}
