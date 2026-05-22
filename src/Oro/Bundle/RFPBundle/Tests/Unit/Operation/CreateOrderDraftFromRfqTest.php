<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Operation;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Operation\CreateOrderDraftFromRfq;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateOrderDraftFromRfqTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;

    private CreateOrderDraftFromRfq $operation;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $this->operation = new CreateOrderDraftFromRfq($this->orderDraftManager);
    }

    public function testCreateOrderDraftFromRfqCallsManagerAndReturnsOrder(): void
    {
        $request = new Request();
        $expectedOrder = new Order();

        $this->orderDraftManager
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with(
                self::identicalTo($request),
                self::callback(static function (string $uuid): bool {
                    return preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                        $uuid
                    ) === 1;
                })
            )
            ->willReturn($expectedOrder);

        $result = $this->operation->createOrderDraftFromRfq($request);

        self::assertSame($expectedOrder, $result);
    }
}
