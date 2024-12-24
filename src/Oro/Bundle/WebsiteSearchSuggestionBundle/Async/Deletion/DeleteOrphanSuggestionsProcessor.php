<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
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
    public function __construct(
        private MessageProducerInterface $producer,
        private ManagerRegistry $doctrine,
        private int $bufferSize
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $count = 0;
        $suggestionsIds = [];

        foreach ($this->getResultIterator() as $row) {
            $suggestionsIds[] = $row['id'];

            $count++;
            if ($count % $this->bufferSize === 0) {
                $this->producer->send(DeleteOrphanSuggestionsChunkTopic::getName(), [
                    DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => $suggestionsIds
                ]);
                $suggestionsIds = [];
            }
        }

        if ($suggestionsIds) {
            $this->producer->send(DeleteOrphanSuggestionsChunkTopic::getName(), [
                DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => $suggestionsIds
            ]);
        }

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [DeleteOrphanSuggestionsTopic::getName()];
    }

    private function getResultIterator(): \Iterator
    {
        $iterator = new BufferedIdentityQueryResultIterator(
            $this->getSuggestionRepository()->getSuggestionIdsWithEmptyProductsQB()
        );
        $iterator->setBufferSize($this->bufferSize);

        return $iterator;
    }

    private function getSuggestionRepository(): SuggestionRepository
    {
        return $this->doctrine->getRepository(Suggestion::class);
    }
}
