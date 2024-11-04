<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderStatusListener;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class OrderStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ORDER_PROCESSING_WORKFLOW_GROUP = 'order_processing_workflow';

    /** @var OrderConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var OrderStatusListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(OrderConfigurationProviderInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($this->entityRepository);

        $this->listener = new OrderStatusListener(
            $this->configurationProvider,
            $doctrine,
            $this->workflowManager,
            self::ORDER_PROCESSING_WORKFLOW_GROUP
        );
    }

    public function testPrePersistWhenInternalStatusIsNotSetYet(): void
    {
        $order = new OrderStub();
        $defaultInternalStatusId = OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN;
        $defaultInternalStatus = new TestEnumValue(Order::INTERNAL_STATUS_CODE, 'Open', $defaultInternalStatusId);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(self::identicalTo($order), self::ORDER_PROCESSING_WORKFLOW_GROUP)
            ->willReturn(null);
        $this->configurationProvider->expects(self::once())
            ->method('getNewOrderInternalStatus')
            ->with(self::identicalTo($order))
            ->willReturn($defaultInternalStatusId);
        $this->entityRepository->expects(self::once())
            ->method('find')
            ->with($defaultInternalStatusId)
            ->willReturn($defaultInternalStatus);

        $this->listener->prePersist($order);
        self::assertSame($defaultInternalStatus, $order->getInternalStatus());
    }

    public function testPrePersistWhenInternalStatusIsNotSetYetAndNoDefaultStatusIsNotConfigured(): void
    {
        $order = new OrderStub();

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(self::identicalTo($order), self::ORDER_PROCESSING_WORKFLOW_GROUP)
            ->willReturn(null);
        $this->configurationProvider->expects(self::once())
            ->method('getNewOrderInternalStatus')
            ->with(self::identicalTo($order))
            ->willReturn(null);
        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertNull($order->getInternalStatus());
    }

    public function testPrePersistWhenInternalStatusIsAlreadySet(): void
    {
        $orderStatus = new TestEnumValue('test', 'Test', 'test1');
        $order = new OrderStub();
        $order->setInternalStatus($orderStatus);

        $this->workflowManager->expects(self::never())
            ->method('getAvailableWorkflowByRecordGroup');
        $this->configurationProvider->expects(self::never())
            ->method('getNewOrderInternalStatus');
        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertSame($orderStatus, $order->getInternalStatus());
    }

    public function testPrePersistWhenInternalStatusIsNotSetYetAndHasAvailableWorkflowByRecordGroup(): void
    {
        $order = new OrderStub();

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(self::identicalTo($order), self::ORDER_PROCESSING_WORKFLOW_GROUP)
            ->willReturn($this->createMock(Workflow::class));
        $this->configurationProvider->expects(self::never())
            ->method('getNewOrderInternalStatus');
        $this->entityRepository->expects(self::never())
            ->method('find');

        $this->listener->prePersist($order);
        self::assertNull($order->getInternalStatus());
    }
}
