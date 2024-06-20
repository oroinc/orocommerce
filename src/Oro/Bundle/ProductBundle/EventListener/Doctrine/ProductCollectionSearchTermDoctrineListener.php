<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Initiates reindex of the product collection segment of the persisted/updated {@see SearchTerm} entity.
 */
class ProductCollectionSearchTermDoctrineListener implements ResetInterface
{
    /**
     * @var array<int,SearchTerm>
     */
    private array $scheduledEntities = [];

    public function __construct(private readonly MessageProducerInterface $messageProducer)
    {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($this->getAffectedSearchTerms($event) as $entity) {
            $this->scheduledEntities[$entity->getId()] = $entity;
        }
    }

    private function getAffectedSearchTerms(OnFlushEventArgs $event): \Generator
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof SearchTerm && $entity->getProductCollectionSegment()) {
                yield $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof SearchTerm) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['productCollectionSegment'])) {
                    yield $entity;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        foreach ($this->scheduledEntities as $searchTerm) {
            $this->messageProducer->send(
                SearchTermProductCollectionSegmentReindexTopic::getName(),
                [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
                ]
            );
        }

        $this->scheduledEntities = [];
    }

    public function onClear(): void
    {
        $this->scheduledEntities = [];
    }

    public function reset(): void
    {
        $this->scheduledEntities = [];
    }
}
