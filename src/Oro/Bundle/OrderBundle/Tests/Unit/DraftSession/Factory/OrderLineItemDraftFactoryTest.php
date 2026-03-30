<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\OrderBundle\DraftSession\Factory\OrderLineItemDraftFactory;
use Oro\Bundle\OrderBundle\DraftSession\OrderLineItemDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftFactoryTest extends TestCase
{
    private OrderLineItemDraftSynchronizer&MockObject $entityDraftSynchronizer;
    private OrderLineItemDraftFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftSynchronizer = $this->createMock(OrderLineItemDraftSynchronizer::class);

        $this->factory = new OrderLineItemDraftFactory($this->entityDraftSynchronizer);
    }

    public function testCreateDraftCreatesNewOrderLineItemDraft(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 100);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-123');

        self::assertNotSame($lineItem, $lineItemDraft);
    }

    public function testCreateDraftSetsDraftSessionUuid(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 100);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'test-uuid-456');

        self::assertEquals('test-uuid-456', $lineItemDraft->getDraftSessionUuid());
    }

    public function testCreateDraftSetsDraftSourceToLineItemWhenLineItemHasId(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 200);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-789');

        self::assertSame($lineItem, $lineItemDraft->getDraftSource());
    }

    public function testCreateDraftSetsDraftSourceToNullWhenLineItemHasNoId(): void
    {
        $lineItem = new OrderLineItem();

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft');

        self::assertNull($lineItem->getId());

        $lineItemDraft = $this->factory->createDraft($lineItem, 'uuid-abc');

        self::assertNull($lineItemDraft->getDraftSource());
    }

    public function testCreateDraftCallsSynchronizeToDraft(): void
    {
        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 300);

        $this->entityDraftSynchronizer->expects(self::once())
            ->method('synchronizeToDraft')
            ->with(
                self::identicalTo($lineItem),
                self::isInstanceOf(OrderLineItem::class)
            );

        $this->factory->createDraft($lineItem, 'uuid-def');
    }
}
