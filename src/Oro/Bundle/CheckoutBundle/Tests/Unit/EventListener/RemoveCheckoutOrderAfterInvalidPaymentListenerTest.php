<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveCheckoutOrderAfterInvalidPaymentListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveCheckoutOrderAfterInvalidPaymentListenerTest extends TestCase
{
    private RemoveCheckoutOrderAfterInvalidPaymentListener $afterInvalidPaymentListener;

    private ManagerRegistry|MockObject $managerRegsitry;

    private CouponUsageManager|MockObject $couponUsageManager;

    protected function setUp(): void
    {
        $this->managerRegsitry = self::createMock(ManagerRegistry::class);
        $this->couponUsageManager = self::createMock(CouponUsageManager::class);

        $this->afterInvalidPaymentListener = new RemoveCheckoutOrderAfterInvalidPaymentListener($this->managerRegsitry);
        $this->afterInvalidPaymentListener->setCouponUsageManager($this->couponUsageManager);

        $this->afterInvalidPaymentListener
            ->addTransitionName('place_order')
            ->addTransitionName('create_order');
    }

    public function testOnBeforeOrderCreate(): void
    {
        $customerUser = self::createMock(CustomerUser::class);
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

        $checkout = (new Checkout())->setOrder($order);
        $definition = (new WorkflowDefinition())->setMetadata(['is_checkout_workflow' => true]);
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $this->couponUsageManager->expects(self::once())
            ->method('revertCouponUsages')
            ->with($appliedCoupons, $customerUser);

        $objectManager = self::createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('remove')
            ->with($order);

        $this->managerRegsitry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
        self::assertNull($checkout->getOrder());
    }

    public function testOnBeforeOrderCreateWithAnyWorkflow(): void
    {
        $checkout = new Checkout();
        $definition = new WorkflowDefinition();
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $this->managerRegsitry->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithNotSupportedTransition(): void
    {
        $definition = (new WorkflowDefinition())->setMetadata(['is_checkout_workflow' => true]);
        $event = self::assertCheckoutTransitionBeforeEvent(new Checkout(), $definition, 'not_supported');

        $this->managerRegsitry->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithoutOrder(): void
    {
        $checkout = new Checkout();
        $definition = (new WorkflowDefinition())->setMetadata(['is_checkout_workflow' => true]);
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'place_order');

        $this->couponUsageManager->expects(self::never())
            ->method('revertCouponUsages');
        $this->managerRegsitry->expects(self::once())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    private function assertCheckoutTransitionBeforeEvent(
        ?Checkout $checkout,
        ?WorkflowDefinition $definition,
        ?string $transitionName
    ): CheckoutTransitionBeforeEvent {
        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowItem->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);

        $transition = self::createMock(Transition::class);
        $transition->expects(self::any())
            ->method('getName')
            ->willReturn($transitionName);

        return new CheckoutTransitionBeforeEvent($workflowItem, $transition);
    }
}
