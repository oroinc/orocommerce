<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * The processor creates chunk data of orphan suggestion ids and sends
 * them to DeleteOrphanSuggestionsChunkProcessor.php for removal.
 */
class DeleteOrphanSuggestionsProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    private const BUFFER_SIZE = 1000;

    public function __construct(
        private MessageProducerInterface $producer,
        private ManagerRegistry $doctrine,
        private int $bufferSize = self::BUFFER_SIZE
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $suggestionIdsWithEmptyProducts = $this->getSuggestionRepository()
            ->getSuggestionIdsWithEmptyProducts();

        foreach (array_chunk($suggestionIdsWithEmptyProducts, $this->bufferSize) as $idsChunk) {
            $this->producer->send(DeleteOrphanSuggestionsChunkTopic::getName(), [
                DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => $idsChunk
            ]);
        }

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [DeleteOrphanSuggestionsTopic::getName()];
    }

    private function getSuggestionRepository(): SuggestionRepository
    {
        return $this->doctrine->getRepository(Suggestion::class);
    }
}
