<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveCheckoutOrderAfterInvalidPaymentListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveCheckoutOrderAfterInvalidPaymentListenerTest extends TestCase
{
    /** @var RemoveCheckoutOrderAfterInvalidPaymentListener */
    private $afterInvalidPaymentListener;

    /** @var ManagerRegistry|MockObject */
    private $managerRegsitry;

    protected function setUp(): void
    {
        $this->managerRegsitry = self::createMock(ManagerRegistry::class);
        $this->afterInvalidPaymentListener = new RemoveCheckoutOrderAfterInvalidPaymentListener($this->managerRegsitry);
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

    public function testOnBeforeOrderCreate(): void
    {
        $order = new Order();
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $orderRepository = self::createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uuid' => $checkout->getUuid()])
            ->willReturn($order);

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
            ->expects(self::any())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegsitry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $objectManager = self::createMock(ObjectManager::class);


        $this->managerRegsitry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithoutSupportedExclusiveRecordGroups(): void
    {
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['any']);
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'create_order');

        $this->managerRegsitry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithoutOrder(): void
    {
        $checkout = (new Checkout())->setUuid(UUIDGenerator::v4());
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::assertCheckoutTransitionBeforeEvent($checkout, $definition, 'place_order');

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
            ->expects(self::any())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->managerRegsitry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($objectManager);

        $objectManager = self::createMock(ObjectManager::class);

        $this->managerRegsitry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    public function testOnBeforeOrderCreateWithNotSupportedTransition(): void
    {
        $definition = (new WorkflowDefinition())->setExclusiveRecordGroups(['b2b_checkout_flow']);
        $event = self::assertCheckoutTransitionBeforeEvent(new Checkout(), $definition, 'not_supported');

        $this->managerRegsitry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->afterInvalidPaymentListener->onBeforeOrderCreate($event);
    }

    private function assertCheckoutTransitionBeforeEvent(
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
