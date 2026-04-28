<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveCheckoutOrderAfterInvalidPaymentListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RemoveCheckoutOrderAfterInvalidPaymentListenerTest extends TestCase
{
    private RemoveCheckoutOrderAfterInvalidPaymentListener $afterInvalidPaymentListener;

    private ManagerRegistry|MockObject $managerRegistry;

    private CouponUsageManager|MockObject $couponUsageManager;

    protected function setUp(): void
    {
        $this->managerRegistry = self::createMock(ManagerRegistry::class);
        $this->couponUsageManager = self::createMock(CouponUsageManager::class);

        $this->afterInvalidPaymentListener = new RemoveCheckoutOrderAfterInvalidPaymentListener($this->managerRegistry);
        $this->afterInvalidPaymentListener->setCouponUsageManager($this->couponUsageManager);

        $this->afterInvalidPaymentListener
            ->addTransitionName('place_order')
            ->addTransitionName('create_order');
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [CheckoutTransitionBeforeEvent::class => 'onBeforeOrderCreate'],
            RemoveCheckoutOrderAfterInvalidPaymentListener::getSubscribedEvents()
        );
    }

    public function testOnCheckoutRequestRevertsOnPaymentErrorTransition(): void
    {
        $customerUser = self::createMock(CustomerUser::class);

        $order = $this->getMockBuilder(Order::class)
            ->addMethods(['getAppliedCoupons'])
            ->onlyMethods(['getCustomerUser'])
            ->getMock();
        $order->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn(null);
        $order->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());

        $orderRepository = self::createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn($order);

        $objectManager = self::createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $this->couponUsageManager->expects(self::once())
            ->method('revertCouponUsages')
            ->with(null, $customerUser);

        $request = Request::create('/checkout/1', 'GET', ['transition' => 'payment_error']);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->afterInvalidPaymentListener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestSkipsWhenNotPaymentErrorTransition(): void
    {
        $request = Request::create('/checkout/1', 'GET', ['transition' => 'next_step']);
        $event = new CheckoutRequestEvent($request, new Checkout());

        $this->managerRegistry->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestSkipsWhenCheckoutCompleted(): void
    {
        $checkout = self::createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('isCompleted')
            ->willReturn(true);

        $request = Request::create('/checkout/1', 'GET', ['transition' => 'payment_error']);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->managerRegistry->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestSkipsWhenNoOrder(): void
    {
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());

        $orderRepository = self::createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn(null);

        $objectManager = self::createMock(ObjectManager::class);
        $objectManager->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $this->couponUsageManager->expects(self::never())
            ->method('revertCouponUsages');

        $request = Request::create('/checkout/1', 'GET', ['transition' => 'payment_error']);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->afterInvalidPaymentListener->onCheckoutRequest($event);
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

        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::buildCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $orderRepository = self::createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn($order);

        $this->couponUsageManager->expects(self::once())
            ->method('revertCouponUsages')
            ->with($appliedCoupons, $customerUser);

        $objectManager = self::createMock(ObjectManager::class);
        $objectManager
            ->expects(self::once())
            ->method('remove')
            ->with($order);
        $objectManager
            ->expects(self::once())
            ->method('flush')
            ->with($order);
        $objectManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithoutSupportedExclusiveRecordGroups(): void
    {
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['any']);
        $event = self::buildCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $this->managerRegistry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithoutOrder(): void
    {
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::buildCheckoutTransitionBeforeEvent($checkout, $definition, 'place_order');

        $this->couponUsageManager->expects(self::never())
            ->method('revertCouponUsages');

        $orderRepository = self::createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn(null);

        $objectManager = self::createMock(ObjectManager::class);
        $objectManager
            ->expects(self::never())
            ->method('remove');
        $objectManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithNotSupportedTransition(): void
    {
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::buildCheckoutTransitionBeforeEvent(new Checkout(), $definition, 'not_supported');

        $this->managerRegistry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    private function buildCheckoutTransitionBeforeEvent(
        ?Checkout $checkout,
        ?WorkflowDefinition $definition,
        ?string $transitionName
    ): CheckoutTransitionBeforeEvent {
        $workflowItem = self::createMock(WorkflowItem::class);
        $workflowItem
            ->expects(self::any())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowItem
            ->expects(self::any())
            ->method('getDefinition')
            ->willReturn($definition);
        $workflowItem
            ->expects(self::any())
            ->method('getData')
            ->willReturn(new WorkflowData());

        $transition = self::createMock(Transition::class);
        $transition
            ->expects(self::any())
            ->method('getName')
            ->willReturn($transitionName);

        return new CheckoutTransitionBeforeEvent($workflowItem, $transition);
    }
}
