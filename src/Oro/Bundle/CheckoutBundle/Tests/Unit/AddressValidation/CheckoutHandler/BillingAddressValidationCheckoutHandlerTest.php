<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\AddressValidation\CheckoutHandler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler\BillingAddressValidationCheckoutHandler;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BillingAddressValidationCheckoutHandlerTest extends TestCase
{
    private CheckoutWorkflowHelper&MockObject $checkoutWorkflowHelper;

    private UpdateCheckoutStateInterface&MockObject $updateCheckoutStateAction;

    private BillingAddressValidationCheckoutHandler $handler;

    private EntityManager&MockObject $entityManager;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->updateCheckoutStateAction = $this->createMock(UpdateCheckoutStateInterface::class);

        $this->handler = new BillingAddressValidationCheckoutHandler(
            $doctrine,
            $this->checkoutWorkflowHelper,
            $this->updateCheckoutStateAction
        );

        $this->entityManager = $this->createMock(EntityManager::class);
        $doctrine
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($this->entityManager);
    }

    public function testHandleUpdatesBillingAddressAndFlushesEntityManager(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($oldAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($oldAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $stateToken = 'test_state_token';
        $workflowData = new WorkflowData(['state_token' => $stateToken]);
        $workflowItem = (new WorkflowItem())
            ->setData($workflowData);

        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->updateCheckoutStateAction
            ->expects(self::once())
            ->method('execute')
            ->with($checkout, $stateToken, true, true)
            ->willReturn(true);

        $this->handler->handle($checkout, $newAddress);

        self::assertSame($newAddress, $checkout->getBillingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleUpdatesBillingAddressWhenNoStateToken(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($oldAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($oldAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $workflowData = new WorkflowData();
        $workflowItem = (new WorkflowItem())
            ->setData($workflowData);

        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->updateCheckoutStateAction
            ->expects(self::never())
            ->method('execute');

        $this->handler->handle($checkout, $newAddress);

        self::assertSame($newAddress, $checkout->getBillingAddress());
    }

    public function testHandleUpdatesWorkflowDataWithSubmittedWorkflowData(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($oldAddress);
        $submittedWorkflowData = new WorkflowData();
        $submittedWorkflowData->set('sample_key', 'sample_value');

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($oldAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $stateToken = 'test_state_token';
        $workflowData = new WorkflowData(['state_token' => $stateToken]);
        $workflowItem = (new WorkflowItem())
            ->setData($workflowData);

        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->updateCheckoutStateAction
            ->expects(self::once())
            ->method('execute')
            ->with($checkout, $stateToken, true, true)
            ->willReturn(true);

        $oldUpdatedAt = $workflowItem->getUpdated();

        $this->handler->handle($checkout, $newAddress, $submittedWorkflowData);

        self::assertEquals('sample_value', $workflowItem->getData()->get('sample_key'));
        self::assertNotEquals($oldUpdatedAt, $workflowItem->getUpdated());

        self::assertSame($newAddress, $checkout->getBillingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleDoesNotRemoveOldAddressIfSameAsNewAddress(): void
    {
        $billingAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setBillingAddress($billingAddress);

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $stateToken = 'test_state_token';
        $workflowData = new WorkflowData(['state_token' => $stateToken]);
        $workflowItem = (new WorkflowItem())
            ->setData($workflowData);

        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->updateCheckoutStateAction
            ->expects(self::once())
            ->method('execute')
            ->with($checkout, $stateToken, true, true)
            ->willReturn(true);

        $this->handler->handle($checkout, $billingAddress);

        self::assertSame($billingAddress, $checkout->getBillingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleHandlesNullOldAddress(): void
    {
        $checkout = new Checkout();
        $newAddress = new OrderAddress();

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $stateToken = 'test_state_token';
        $workflowData = new WorkflowData(['state_token' => $stateToken]);
        $workflowItem = (new WorkflowItem())
            ->setData($workflowData);

        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->updateCheckoutStateAction
            ->expects(self::once())
            ->method('execute')
            ->with($checkout, $stateToken, true, true)
            ->willReturn(true);

        $this->handler->handle($checkout, $newAddress);

        self::assertSame($newAddress, $checkout->getBillingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }
}
