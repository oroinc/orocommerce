<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\AddOrderDraftSessionParametersDatagridListener;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddOrderDraftSessionParametersDatagridListenerTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;
    private AddOrderDraftSessionParametersDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->listener = new AddOrderDraftSessionParametersDatagridListener($this->orderDraftManager);
    }

    public function testOnBuildBeforeWhenNoDraftSessionUuid(): void
    {
        $parameterBag = new ParameterBag(['some_param' => 'value']);
        $datagridConfig = DatagridConfiguration::create([]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::never())
            ->method('getConfig');

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findOrderDraft');

        $this->listener->onBuildBefore($event);

        self::assertNull($parameterBag->get('order_draft_id'));
    }

    public function testOnBuildBeforeWhenDraftSessionUuidIsNotScalar(): void
    {
        $parameterBag = new ParameterBag(['draft_session_uuid' => ['array']]);
        $datagridConfig = DatagridConfiguration::create([]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::never())
            ->method('getConfig');

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findOrderDraft');

        $this->listener->onBuildBefore($event);

        self::assertNull($parameterBag->get('order_draft_id'));
    }

    public function testOnBuildBeforeWhenOrderDraftIdAlreadyPresent(): void
    {
        $parameterBag = new ParameterBag([
            'draft_session_uuid' => 'test-uuid',
            'order_draft_id' => 123
        ]);
        $datagridConfig = DatagridConfiguration::create([]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::never())
            ->method('getConfig');

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findOrderDraft');

        $this->listener->onBuildBefore($event);

        self::assertEquals(123, $parameterBag->get('order_draft_id'));
    }

    public function testOnBuildBeforeWhenOrderDraftNotFound(): void
    {
        $draftSessionUuid = 'test-uuid-123';
        $parameterBag = new ParameterBag(['draft_session_uuid' => $draftSessionUuid]);
        $datagridConfig = DatagridConfiguration::create([]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::never())
            ->method('getConfig');

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn(null);

        $this->listener->onBuildBefore($event);

        self::assertNull($parameterBag->get('order_draft_id'));
    }

    public function testOnBuildBeforeWhenOrderDraftFound(): void
    {
        $draftSessionUuid = 'test-uuid-456';
        $orderDraftId = 789;
        $parameterBag = new ParameterBag(['draft_session_uuid' => $draftSessionUuid]);
        $datagridConfig = DatagridConfiguration::create([]);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, $orderDraftId);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn($orderDraft);

        $this->listener->onBuildBefore($event);

        self::assertEquals($orderDraftId, $parameterBag->get('order_draft_id'));
        self::assertEquals(
            ['order_draft_id' => $orderDraftId],
            $datagridConfig->offsetGetByPath('[options][urlParams]')
        );
    }

    public function testOnBuildBeforeWhenOrderDraftFoundAndExistingUrlParams(): void
    {
        $draftSessionUuid = 'test-uuid-999';
        $orderDraftId = 555;
        $parameterBag = new ParameterBag(['draft_session_uuid' => $draftSessionUuid]);
        $datagridConfig = DatagridConfiguration::create([
            'options' => [
                'urlParams' => [
                    'existing_param' => 'existing_value'
                ]
            ]
        ]);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, $orderDraftId);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameterBag);
        $datagrid
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $event = new BuildBefore($datagrid, $datagridConfig);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findOrderDraft')
            ->with($draftSessionUuid)
            ->willReturn($orderDraft);

        $this->listener->onBuildBefore($event);

        self::assertEquals($orderDraftId, $parameterBag->get('order_draft_id'));
        self::assertEquals(
            [
                'existing_param' => 'existing_value',
                'order_draft_id' => $orderDraftId
            ],
            $datagridConfig->offsetGetByPath('[options][urlParams]')
        );
    }
}
