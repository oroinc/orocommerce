<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistSuggestionPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
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

        $allProductsPhrases = $this->suggestionProvider->getLocalizedSuggestionPhrasesGroupedByProductId(
            $productIds
        );

        foreach ($allProductsPhrases as $localizationId => $phrases) {
            foreach (array_chunk($phrases, $this->chunkSize, true) as $phrasesChunk) {
                $this->sendPhrasesChunkMessage($phrasesChunk, $organization, $localizationId);
            }
        }

        return self::ACK;
    }

    private function sendPhrasesChunkMessage(array $phrasesChunk, int $organization, int $localizationId): void
    {
        $phrasesAndIds = [];

        foreach ($phrasesChunk as $phrase => $ids) {
            $phrasesAndIds[$phrase] = array_unique($ids);
        }

        $this->producer->send(
            PersistSuggestionPhrasesChunkTopic::getName(),
            [
                PersistSuggestionPhrasesChunkTopic::ORGANIZATION => $organization,
                PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                    $localizationId => $phrasesAndIds
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
        /**
         * @var ProductSuggestionRepository $repository
         */
        $repository = $this->doctrine->getRepository(ProductSuggestion::class);

        $repository->clearProductSuggestionsByProductIds($productIds);
    }
}
