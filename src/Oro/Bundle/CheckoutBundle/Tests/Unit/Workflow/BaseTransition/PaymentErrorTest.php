<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PaymentError;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentErrorTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private TransitionServiceInterface|MockObject $baseTransition;

    private PaymentError $paymentError;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);

        $this->paymentError = new PaymentError(
            $this->registry,
            $this->baseTransition
        );
    }

    public function testExecuteRemovesOrder(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = $this->createMock(Order::class);
        $data = new WorkflowData(['order' => $order]);

        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('remove')
            ->with($order);

        $this->paymentError->execute($workflowItem);

        $this->assertNull($data->offsetGet('order'));
    }

    public function testExecuteDoesNotRemoveOrderIfNotPresent(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $data = new WorkflowData(['order' => null]);

        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $this->paymentError->execute($workflowItem);

        $this->assertNull($data->offsetGet('order'));
    }
}
