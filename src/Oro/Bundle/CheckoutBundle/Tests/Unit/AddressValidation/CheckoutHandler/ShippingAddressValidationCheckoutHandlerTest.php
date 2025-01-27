<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\AddressValidation\CheckoutHandler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler\ShippingAddressValidationCheckoutHandler;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingAddressValidationCheckoutHandlerTest extends TestCase
{
    private CheckoutWorkflowHelper&MockObject $checkoutWorkflowHelper;

    private UpdateCheckoutStateInterface&MockObject $updateCheckoutStateAction;

    private ShippingAddressValidationCheckoutHandler $handler;

    private EntityManager&MockObject $entityManager;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->updateCheckoutStateAction = $this->createMock(UpdateCheckoutStateInterface::class);

        $this->handler = new ShippingAddressValidationCheckoutHandler(
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

    public function testHandleResetsShipToBillingFlagIfAddressDiffersFromBillingAddress(): void
    {
        $billingAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setShipToBillingAddress(true)
            ->setBillingAddress($billingAddress);

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

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($newAddress, $checkout->getShippingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleUpdatesShippingAddressAndFlushesEntityManager(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setShippingAddress($oldAddress);

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

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($newAddress, $checkout->getShippingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleUpdatesShippingAddressWhenNoStateToken(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setShippingAddress($oldAddress);

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

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($newAddress, $checkout->getShippingAddress());
    }

    public function testHandleUpdatesWorkflowDataWithSubmittedWorkflowData(): void
    {
        $oldAddress = new OrderAddress();
        $newAddress = new OrderAddress();
        $checkout = (new Checkout())
            ->setShippingAddress($oldAddress);
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

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($newAddress, $checkout->getShippingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandleDoesNotReplaceSameShippingAddress(): void
    {
        $address = new OrderAddress();
        $checkout = (new Checkout())
            ->setShippingAddress($address);

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

        $this->handler->handle($checkout, $address);

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($address, $checkout->getShippingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }

    public function testHandlesNullShippingAddress(): void
    {
        $newAddress = new OrderAddress();
        $checkout = new Checkout();

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

        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertSame($newAddress, $checkout->getShippingAddress());
        self::assertTrue($workflowItem->getResult()->get('updateCheckoutState'));
    }
}
