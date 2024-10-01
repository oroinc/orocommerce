<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsByProductCollectionSegmentsProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Initiates reindex of the product collection segment of the persisted/updated {@see Segment} entity
 * referenced by a {@see SearchTerm}.
 */
class ProductCollectionSearchTermSegmentDoctrineListener implements ResetInterface
{
    /**
     * @var array<int,Segment>
     */
    private array $scheduledSegments = [];

    public function __construct(
        private readonly MessageProducerInterface $messageProducer,
        private readonly SearchTermsByProductCollectionSegmentsProvider $searchTermsByProductCollectionSegmentsProvider
    ) {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $this->scheduledSegments = [];
        foreach ($this->getAffectedSegments($event) as $segment) {
            $this->scheduledSegments[$segment->getId()] = $segment;
        }
    }

    private function getAffectedSegments(OnFlushEventArgs $event): \Generator
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Segment && $entity->getEntity() === Product::class) {
                yield $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Segment && $entity->getEntity() === Product::class) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['definition'])) {
                    yield $entity;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->scheduledSegments) {
            return;
        }

        $searchTerms = $this->searchTermsByProductCollectionSegmentsProvider
            ->getRelatedSearchTerms(array_keys($this->scheduledSegments));

        foreach ($searchTerms as $searchTerm) {
            $this->messageProducer->send(
                SearchTermProductCollectionSegmentReindexTopic::getName(),
                [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
                ]
            );
        }

        $this->scheduledSegments = [];
    }

    public function onClear(): void
    {
        $this->scheduledSegments = [];
    }

    #[\Override]
    public function reset(): void
    {
        $this->scheduledSegments = [];
    }
}
