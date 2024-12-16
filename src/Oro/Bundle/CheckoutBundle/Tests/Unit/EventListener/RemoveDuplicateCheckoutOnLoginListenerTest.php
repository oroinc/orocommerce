<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\EventListener\RemoveDuplicateCheckoutOnLoginListener;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoveDuplicateCheckoutOnLoginListenerTest extends TestCase
{
    private WorkflowManager&MockObject $workflowManager;
    private UserCurrencyManager&MockObject $userCurrencyManager;
    private ManagerRegistry&MockObject $registry;

    private RemoveDuplicateCheckoutOnLoginListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->listener = new RemoveDuplicateCheckoutOnLoginListener(
            $this->workflowManager,
            $this->userCurrencyManager,
            $this->registry,
        );
    }

    public function testNoCheckoutEntity(): void
    {
        $this->workflowManager->expects(self::never())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow');

        $this->listener->onCheckoutLogin(new LoginOnCheckoutEvent());
    }

    public function testNoSourceEntity(): void
    {
        $event = new LoginOnCheckoutEvent();
        $event->setCheckoutEntity((new Checkout())->setSource(new CheckoutSourceStub()));

        $this->workflowManager->expects(self::never())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow');

        $this->listener->onCheckoutLogin($event);
    }

    public function testNoCustomerUser(): void
    {
        $event = new LoginOnCheckoutEvent();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $event->setCheckoutEntity((new Checkout())->setSource($source));

        $this->workflowManager->expects(self::never())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow');

        $this->listener->onCheckoutLogin($event);
    }

    public function testNoAvailableWorkflow(): void
    {
        $event = new LoginOnCheckoutEvent();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $checkout = (new Checkout())->setSource($source);
        $checkout->setCustomerUser(new CustomerUser());

        $event->setCheckoutEntity($checkout);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $this->registry->expects(self::never())
            ->method('getManager');

        $this->listener->onCheckoutLogin($event);
    }

    public function testNoDuplicateCheckouts(): void
    {
        $event = new LoginOnCheckoutEvent();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $checkout = (new Checkout())->setSource($source);
        $checkout->setCustomerUser(new CustomerUser());
        ReflectionUtil::setId($checkout, 1);

        $event->setCheckoutEntity($checkout);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::once())
            ->method('getName')
            ->willReturn('b2b_flow_checkout');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CheckoutRepository::class);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->registry->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('findDuplicateCheckouts')
            ->with(
                $event->getCheckoutEntity()->getCustomerUser(),
                ['shoppingList' => $event->getCheckoutEntity()->getSourceEntity()],
                'b2b_flow_checkout',
                [1],
                'USD'
            )
            ->willReturn([]);

        $em->expects(self::never())->method('flush');

        $this->listener->onCheckoutLogin($event);
    }

    public function testWithDuplicateCheckout(): void
    {
        $event = new LoginOnCheckoutEvent();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $checkout = (new Checkout())->setSource($source);
        $checkout->setCustomerUser(new CustomerUser());
        ReflectionUtil::setId($checkout, 1);

        $event->setCheckoutEntity($checkout);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::once())
            ->method('getName')
            ->willReturn('b2b_flow_checkout');

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CheckoutRepository::class);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->registry->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($repo);

        $repo->expects(self::once())
            ->method('findDuplicateCheckouts')
            ->with(
                $event->getCheckoutEntity()->getCustomerUser(),
                ['shoppingList' => $event->getCheckoutEntity()->getSourceEntity()],
                'b2b_flow_checkout',
                [1],
                'USD'
            )
            ->willReturn([new Checkout()]);

        $em->expects(self::once())
            ->method('remove')
            ->with(new Checkout());

        $em->expects(self::once())
            ->method('flush');

        $this->listener->onCheckoutLogin($event);
    }
}
