<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductCollectionSearchTermDoctrineListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\SearchTermStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductCollectionSearchTermDoctrineListenerTest extends TestCase
{
    private MessageProducerInterface|MockObject $messageProducer;

    private ProductCollectionSearchTermDoctrineListener $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new ProductCollectionSearchTermDoctrineListener($this->messageProducer);
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

    public function testShouldIgnoreInsertedSearchTermWithoutSegment(): void
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
            ->willReturn([new SearchTermStub()]);

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

    public function testShouldSendToMqInsertedSearchTermWithSegment(): void
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

        $searchTerm = (new SearchTermStub(42))
            ->setProductCollectionSegment(new Segment());

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$searchTerm]);

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

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

    public function testShouldIgnoreUpdatedSearchTermWithoutSegment(): void
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

        $searchTerm = new SearchTermStub();
        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$searchTerm]);

        $unitOfWork
            ->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($searchTerm)
            ->willReturn([]);

        $this->listener->onFlush($onFlushEventArgs);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testShouldSendToMqUpdatedSearchTermWithSegment(): void
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

        $searchTerm = (new SearchTermStub(42));

        $unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$searchTerm]);

        $unitOfWork
            ->expects(self::once())
            ->method('getEntityChangeSet')
            ->willReturn(['productCollectionSegment' => [null, new Segment()]]);

        $this->listener->onFlush($onFlushEventArgs);

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
