<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PaymentError;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentErrorTest extends TestCase
{
    private TransitionServiceInterface|MockObject $baseTransition;
    private ManagerRegistry|MockObject $doctrine;
    private PaymentStatusProviderInterface|MockObject $paymentStatusProvider;
    private CouponUsageManager|MockObject $couponUsageManager;
    private PaymentError $paymentError;

    protected function setUp(): void
    {
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);
        $this->couponUsageManager = $this->createMock(CouponUsageManager::class);

        $this->paymentError = new PaymentError(
            $this->doctrine,
            $this->baseTransition
        );
        $this->paymentError->setPaymentProviderManager($this->paymentStatusProvider);
        $this->paymentError->setCouponUsageManager($this->couponUsageManager);
    }

    public function testExecuteRemovesOrder(): void
    {
        $customerUser = $this->createMock(CustomerUser::class);
        $appliedCoupons = new ArrayCollection([(new AppliedCoupon())->setSourceCouponId(1)]);

        $order = $this->getMockBuilder(Order::class)
            ->addMethods(['getAppliedCoupons'])
            ->onlyMethods(['getCustomerUser'])
            ->getMock();
        $order->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn($appliedCoupons);
        $order->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);
        $order->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn(null);

        $data = new WorkflowData(['order' => $order]);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::PENDING);

        $this->couponUsageManager->expects(self::once())
            ->method('revertCouponUsages')
            ->with($appliedCoupons, $customerUser);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
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

        $this->couponUsageManager->expects(self::never())
            ->method('revertCouponUsages');
        $this->paymentStatusProvider
            ->expects($this->never())
            ->method('getPaymentStatus');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->paymentError->execute($workflowItem);

        $this->assertNull($data->offsetGet('order'));
    }

    public function testExecuteDoesNotRemovePaidOrder(): void
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

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $this->paymentError->execute($workflowItem);

        $this->assertSame($order, $data->offsetGet('order'));
    }
}
