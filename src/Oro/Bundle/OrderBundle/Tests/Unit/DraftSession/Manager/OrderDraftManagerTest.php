<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Manager;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\DraftSession\Manager\EntityDraftManager;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftManagerTest extends TestCase
{
    private DraftSessionUuidProvider&MockObject $draftSessionUuidProvider;

    private EntityDraftManager&MockObject $entityDraftManager;

    private OrderDraftManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSessionUuidProvider = $this->createMock(DraftSessionUuidProvider::class);
        $this->entityDraftManager = $this->createMock(EntityDraftManager::class);

        $this->manager = new OrderDraftManager(
            $this->draftSessionUuidProvider,
            $this->entityDraftManager
        );
    }

    public function testGetDraftSessionUuidDelegatesToProvider(): void
    {
        $expectedUuid = 'test-draft-uuid-123';

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($expectedUuid);

        $result = $this->manager->getDraftSessionUuid();

        self::assertSame($expectedUuid, $result);
    }

    public function testGetDraftSessionUuidReturnsNullWhenProviderReturnsNull(): void
    {
        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $result = $this->manager->getDraftSessionUuid();

        self::assertNull($result);
    }

    public function testHasEntityDraftDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($order, 'explicit-uuid')
            ->willReturn(true);

        self::assertTrue($this->manager->hasEntityDraft($order, 'explicit-uuid'));
    }

    public function testHasEntityDraftWithNullUuidDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($order, null)
            ->willReturn(false);

        self::assertFalse($this->manager->hasEntityDraft($order));
    }

    public function testFindEntityDraftDelegatesToEntityDraftManager(): void
    {
        $order = new Order();
        $orderDraft = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($order, 'explicit-uuid')
            ->willReturn($orderDraft);

        self::assertSame($orderDraft, $this->manager->findEntityDraft($order, 'explicit-uuid'));
    }

    public function testFindEntityDraftReturnsNullWhenNotFound(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($order, null)
            ->willReturn(null);

        self::assertNull($this->manager->findEntityDraft($order));
    }

    public function testLoadFromEntityDraftDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($order, 'explicit-uuid')
            ->willReturn($order);

        self::assertSame($order, $this->manager->loadFromEntityDraft($order, 'explicit-uuid'));
    }

    public function testLoadFromEntityDraftWithNullUuidDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($order, null)
            ->willReturn($order);

        self::assertSame($order, $this->manager->loadFromEntityDraft($order));
    }

    public function testSaveToEntityDraftDelegatesToEntityDraftManager(): void
    {
        $order = new Order();
        $orderDraft = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with($order, 'explicit-uuid')
            ->willReturn($orderDraft);

        self::assertSame($orderDraft, $this->manager->saveToEntityDraft($order, 'explicit-uuid'));
    }

    public function testSaveToEntityDraftWithNullUuidDelegatesToEntityDraftManager(): void
    {
        $order = new Order();
        $orderDraft = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with($order, null)
            ->willReturn($orderDraft);

        self::assertSame($orderDraft, $this->manager->saveToEntityDraft($order));
    }

    public function testDeleteEntityDraftDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('deleteEntityDraft')
            ->with($order, 'explicit-uuid');

        $this->manager->deleteEntityDraft($order, 'explicit-uuid');
    }

    public function testDeleteEntityDraftWithNullUuidDelegatesToEntityDraftManager(): void
    {
        $order = new Order();

        $this->entityDraftManager
            ->expects(self::once())
            ->method('deleteEntityDraft')
            ->with($order, null);

        $this->manager->deleteEntityDraft($order);
    }
}
