<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Oro\Bundle\OrderBundle\DraftSession\RecalculateTotalsOrderDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderStub;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RecalculateTotalsOrderDraftSynchronizerTest extends TestCase
{
    private TotalHelper&MockObject $totalHelper;
    private RecalculateTotalsOrderDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->synchronizer = new RecalculateTotalsOrderDraftSynchronizer($this->totalHelper);
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(OrderLineItem::class));
    }

    public function testSynchronizeFromDraftCallsTotalHelperFill(): void
    {
        $draft = new OrderStub();
        $entity = new OrderStub();

        $this->totalHelper->expects(self::once())
            ->method('fill')
            ->with($entity);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeToDraftDoesNothing(): void
    {
        $entity = new OrderStub();
        $draft = new OrderStub();

        $this->totalHelper->expects(self::never())
            ->method('fill');

        $this->synchronizer->synchronizeToDraft($entity, $draft);
    }
}
