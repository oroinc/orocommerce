<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener\DraftSession;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\EventListener\DraftSession\GenerateChecksumOnOrderLineItemDraftCreatedEventListener;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenerateChecksumOnOrderLineItemDraftCreatedEventListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface&MockObject $lineItemChecksumGenerator;

    private GenerateChecksumOnOrderLineItemDraftCreatedEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new GenerateChecksumOnOrderLineItemDraftCreatedEventListener(
            $this->lineItemChecksumGenerator,
        );
    }

    public function testOnEntityDraftCreatedIgnoresWhenEntityIsNotRequest(): void
    {
        $notARequest = $this->createMock(EntityDraftAwareInterface::class);
        $orderDraft = new Order();
        $event = new EntityDraftCreatedEvent($notARequest, $orderDraft);

        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedIgnoresWhenDraftIsNotOrder(): void
    {
        $request = new Request();
        $notAnOrder = $this->createMock(EntityDraftAwareInterface::class);
        $event = new EntityDraftCreatedEvent($request, $notAnOrder);

        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }

    public function testOnEntityDraftCreatedSetsChecksumOnAllLineItems(): void
    {
        $request = new Request();
        $orderDraft = $this->createMock(Order::class);

        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();

        $orderDraft
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem1, $lineItem2]));

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->lineItemChecksumGenerator
            ->expects(self::exactly(2))
            ->method('getChecksum')
            ->willReturnMap([
                [$lineItem1, 'checksum-1'],
                [$lineItem2, 'checksum-2'],
            ]);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame('checksum-1', $lineItem1->getChecksum());
        self::assertSame('checksum-2', $lineItem2->getChecksum());
    }

    public function testOnEntityDraftCreatedSetsEmptyStringWhenChecksumGeneratorReturnsNull(): void
    {
        $request = new Request();
        $orderDraft = $this->createMock(Order::class);

        $lineItem = new OrderLineItem();

        $orderDraft
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$lineItem]));

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        $this->listener->onEntityDraftCreated($event);

        self::assertSame('', $lineItem->getChecksum());
    }

    public function testOnEntityDraftCreatedDoesNothingWhenOrderHasNoLineItems(): void
    {
        $request = new Request();
        $orderDraft = $this->createMock(Order::class);

        $orderDraft
            ->method('getLineItems')
            ->willReturn(new ArrayCollection());

        $event = new EntityDraftCreatedEvent($request, $orderDraft);

        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method('getChecksum');

        $this->listener->onEntityDraftCreated($event);
    }
}
