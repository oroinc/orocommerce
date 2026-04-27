<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PaymentError;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentErrorTest extends TestCase
{
    private TransitionServiceInterface|MockObject $baseTransition;
    private ManagerRegistry|MockObject $doctrine;
    private PaymentStatusManager|MockObject $paymentStatusManager;
    private PaymentError $paymentError;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);

        $this->paymentError = new PaymentError(
            $this->baseTransition,
            $this->doctrine,
            $this->paymentStatusManager
        );
    }

    public function testExecuteRemovesOrder(): void
    {
        $order = $this->createMock(Order::class);

        $checkout = new Checkout();
        $checkout->setPaymentInProgress(true);
        $checkout->setOrder($order);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects(self::once())
            ->method('execute')
            ->with($workflowItem);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PENDING));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);
        $entityManager->expects(self::once())
            ->method('remove')
            ->with($order);

        $this->paymentError->execute($workflowItem);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertNull($checkout->getOrder());
    }

    public function testExecuteDoesNotRemoveOrderIfNotPresent(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentInProgress(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects(self::once())
            ->method('execute')
            ->with($workflowItem);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->paymentStatusManager
            ->expects(self::never())
            ->method('getPaymentStatus');

        $this->paymentError->execute($workflowItem);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertNull($checkout->getOrder());
    }

    public function testExecuteDoesNotRemovePaidOrder(): void
    {
        $order = $this->createMock(Order::class);

        $checkout = new Checkout();
        $checkout->setPaymentInProgress(true);
        $checkout->setOrder($order);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects(self::once())
            ->method('execute')
            ->with($workflowItem);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->paymentError->execute($workflowItem);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertSame($order, $checkout->getOrder());
    }
}
