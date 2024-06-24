<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductCollectionSearchTermSegmentDoctrineListener;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsByProductCollectionSegmentsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\SegmentStub;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Initiates reindex of the product collection segment of the persisted/updated {@see Segment} entity
 * referenced by a {@see SearchTerm}.
 */
class ProductCollectionSearchTermSegmentDoctrineListenerTest extends TestCase
{
    private MessageProducerInterface|MockObject $messageProducer;

    private SearchTermsByProductCollectionSegmentsProvider|MockObject $productCollectionSegmentSearchTermsProvider;

    private ProductCollectionSearchTermSegmentDoctrineListener $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->productCollectionSegmentSearchTermsProvider = $this->createMock(
            SearchTermsByProductCollectionSegmentsProvider::class
        );

        $this->listener = new ProductCollectionSearchTermSegmentDoctrineListener(
            $this->messageProducer,
            $this->productCollectionSegmentSearchTermsProvider
        );
    }

    public function testShouldIgnoreNotApplicableInsertedEntity(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldIgnoreInsertedSegmentNotProduct(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Segment()]);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldSendToMqSearchTermOfInsertedSegment(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $segment = (new SegmentStub(42))
            ->setEntity(Product::class);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$segment]);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

        $searchTerm = new SearchTermStub(142);
        $this->productCollectionSegmentSearchTermsProvider
            ->expects(self::once())
            ->method('getRelatedSearchTerms')
            ->with([$segment->getId()])
            ->willReturn([$searchTerm]);

        $this->messageProducer
            ->expects(self::once())
            ->method('send')
            ->with(
                SearchTermProductCollectionSegmentReindexTopic::getName(),
                [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
                ],
            );

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));

        // Ensures scheduled entities are not sent twice.
        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldIgnoreNotApplicableUpdatedEntity(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new \stdClass()]);

        $this->listener->onFlush($onFlushEventArgs);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldIgnoreUpdatedSegmentNotProduct(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $segment = (new Segment())
            ->setEntity(Product::class);
        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$segment]);

        $unitOfWork
            ->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($segment)
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldSendToMqSearchTermOfUpdatedSegment(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $onFlushEventArgs
            ->expects(self::once())
            ->method('getObjectManager')
            ->willReturn($entityManager);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $segment = (new SegmentStub(42))
            ->setEntity(Product::class);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$segment]);

        $unitOfWork
            ->expects(self::once())
            ->method('getEntityChangeSet')
            ->willReturn(['definition' => ['sample-value1', 'sample-value2']]);

        $this->listener->onFlush($onFlushEventArgs);

        $searchTerm = new SearchTermStub(142);
        $this->productCollectionSegmentSearchTermsProvider
            ->expects(self::once())
            ->method('getRelatedSearchTerms')
            ->with([$segment->getId()])
            ->willReturn([$searchTerm]);

        $this->messageProducer
            ->expects(self::once())
            ->method('send')
            ->with(
                SearchTermProductCollectionSegmentReindexTopic::getName(),
                [
                    SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
                ],
            );

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));

        // Ensures scheduled entities are not sent twice.
        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }
}
