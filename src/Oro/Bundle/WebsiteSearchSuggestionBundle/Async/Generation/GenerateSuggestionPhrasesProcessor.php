<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistSuggestionPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\SuggestionProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Iterates products to generate product suggestions
 */
class GenerateSuggestionPhrasesProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const CHUNK_SIZE = 1000;

    public function __construct(
        private MessageProducerInterface $producer,
        private SuggestionProvider $suggestionProvider,
        private ManagerRegistry $doctrine,
        private int $chunkSize = self::CHUNK_SIZE
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $organization = $body[GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION];
        $productIds = $body[GenerateSuggestionsTopic::PRODUCT_IDS];

        $this->clearProductSuggestions($productIds);

        $this->suggestionProvider->setChunkSize($this->chunkSize);
        $allProductsPhrases = $this->suggestionProvider->getLocalizedSuggestionPhrasesGroupedByProductId($productIds);

        if ($this->producer instanceof BufferedMessageProducer) {
            $this->producer->disableBuffering();
        }

        foreach ($allProductsPhrases as $localizationId => $phrases) {
            $this->sendPhrasesChunkMessage($phrases, $organization, $localizationId);
        }

        if ($this->producer instanceof BufferedMessageProducer) {
            $this->producer->enableBuffering();
        }

        return self::ACK;
    }

    private function sendPhrasesChunkMessage(array $phrases, int $organization, int $localizationId): void
    {
        $this->producer->send(
            PersistSuggestionPhrasesChunkTopic::getName(),
            [
                PersistSuggestionPhrasesChunkTopic::ORGANIZATION => $organization,
                PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                    $localizationId => $phrases
                ]
            ]
        );
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [GenerateSuggestionsPhrasesChunkTopic::getName()];
    }

    private function clearProductSuggestions(array $productIds): void
    {
        $this->doctrine->getRepository(ProductSuggestion::class)
            ->clearProductSuggestionsByProductIds($productIds);
    }
}
