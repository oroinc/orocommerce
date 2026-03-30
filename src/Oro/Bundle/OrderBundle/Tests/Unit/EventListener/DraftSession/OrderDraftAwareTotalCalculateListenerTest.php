<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\OrderDraftAwareTotalCalculateListener;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class OrderDraftAwareTotalCalculateListenerTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;
    private FrontendHelper&MockObject $frontendHelper;
    private OrderDraftAwareTotalCalculateListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $this->listener = new OrderDraftAwareTotalCalculateListener(
            $this->orderDraftManager,
            $this->frontendHelper
        );
    }

    public function testOnBeforeTotalCalculateWhenEntityIsNotOrder(): void
    {
        $entity = new \stdClass();
        $request = new Request();

        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->frontendHelper
            ->expects(self::never())
            ->method('isFrontendRequest');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getOrderDraft');

        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenIsFrontendRequest(): void
    {
        $order = new Order();
        $request = new Request();

        $event = new TotalCalculateBeforeEvent($order, $request);

        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getOrderDraft');

        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenOrderDraftNotFound(): void
    {
        $order = new Order();
        $request = new Request();

        $event = new TotalCalculateBeforeEvent($order, $request);

        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getOrderDraft')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('synchronizeEntityFromDraft');

        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testOnBeforeTotalCalculateWhenOrderDraftFound(): void
    {
        $order = new Order();
        $request = new Request();

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 456);

        $event = new TotalCalculateBeforeEvent($order, $request);

        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getOrderDraft')
            ->willReturn($orderDraft);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('synchronizeEntityFromDraft')
            ->with($orderDraft, $order);

        $this->listener->onBeforeTotalCalculate($event);
    }
}
