<?php

namespace Oro\Bundle\ProductBundle\Async\WebsiteSearchTerm;

use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * MQ processor that initiates reindex of the product collection segment of the specified {@see SearchTerm} entity.
 */
class SearchTermProductCollectionSegmentReindexProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SegmentSnapshotDeltaProvider $segmentSnapshotDeltaProvider,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): ?string
    {
        $body = $message->getBody();
        /** @var SearchTerm $searchTerm */
        $searchTerm = $body[SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM];
        if (!$searchTerm || !$segment = $searchTerm->getProductCollectionSegment()) {
            return self::REJECT;
        }

        $websiteIds = [];
        foreach ($searchTerm->getScopes() as $scope) {
            $scopeWebsite = $scope->getWebsite();
            if ($scopeWebsite) {
                $websiteIds[$scopeWebsite->getId()] = $scopeWebsite->getId();
            }
        }

        $websiteIds = array_values($websiteIds);

        foreach ($this->getAffectedProductIds($segment) as $productIds) {
            $this->eventDispatcher->dispatch(
                new ReindexationRequestEvent(
                    [Product::class],
                    $websiteIds,
                    array_column($productIds, 'id'),
                    true,
                    ['main']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );
        }

        return self::ACK;
    }

    private function getAffectedProductIds(Segment $segment): \Generator
    {
        yield from $this->segmentSnapshotDeltaProvider->getAllEntityIds($segment);
        yield from $this->segmentSnapshotDeltaProvider->getRemovedEntityIds($segment);
    }

    public static function getSubscribedTopics(): array
    {
        return [SearchTermProductCollectionSegmentReindexTopic::getName()];
    }
}
