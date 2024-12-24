<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistProductsSuggestionRelationChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\ProductSuggestionPersister;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Processor persists product relations for suggestion to database
 */
class PersistSuggestionProductRelationProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    public function __construct(
        private ProductSuggestionPersister $productSuggestionPersister,
    ) {
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();
        $productsBySuggestions = $body[PersistProductsSuggestionRelationChunkTopic::PRODUCTS_WRAPPER];

        $this->productSuggestionPersister->persistProductSuggestions($productsBySuggestions);

        return self::ACK;
    }

    #[\Override]
    public static function getSubscribedTopics()
    {
        return [PersistProductsSuggestionRelationChunkTopic::getName()];
    }
}
