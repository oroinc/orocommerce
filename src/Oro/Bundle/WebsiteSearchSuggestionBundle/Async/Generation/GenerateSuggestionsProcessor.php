<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\ProductsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Collects products and group them by organization to send for phrases generation
 */
class GenerateSuggestionsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private MessageProducerInterface $messageProducer,
        private ProductsProvider $productsProvider,
        private int $chunkSize
    ) {
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $filterIds = $body[GenerateSuggestionsTopic::PRODUCT_IDS] ?? [];

        $records = $this->productsProvider->getListOfProductIdAndOrganizationId($filterIds);

        $chunk = [];

        foreach ($records as $idx => $record) {
            if ($idx !== 0 && $idx % $this->chunkSize === 0) {
                $this->sendMessagesFromChunk($chunk);
                $chunk = [];
            }

            $chunk[$record['organizationId']][] = $record['id'];
        }

        $this->sendMessagesFromChunk($chunk);

        return static::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [GenerateSuggestionsTopic::getName()];
    }

    private function sendMessagesFromChunk(array $chunk): void
    {
        foreach ($chunk as $organizationId => $ids) {
            $this->messageProducer->send(
                GenerateSuggestionsPhrasesChunkTopic::getName(),
                [
                    GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION => $organizationId,
                    GenerateSuggestionsTopic::PRODUCT_IDS => $ids
                ]
            );
        }
    }
}
