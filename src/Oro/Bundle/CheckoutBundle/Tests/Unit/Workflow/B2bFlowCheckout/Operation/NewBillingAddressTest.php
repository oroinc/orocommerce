<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Operation;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Operation\NewBillingAddress;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NewBillingAddressTest extends TestCase
{
    use EntityTrait;

    private WorkflowManager&MockObject $workflowManager;
    private ActionExecutor&MockObject $actionExecutor;
    private AddressActionsInterface&MockObject $addressActions;
    private UpdateShippingPriceInterface&MockObject $updateShippingPrice;
    private DefaultShippingMethodSetterInterface&MockObject $defaultShippingMethodSetter;
    private NewBillingAddress $operation;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);

        $this->operation = new NewBillingAddress(
            $this->workflowManager,
            $this->actionExecutor,
            $this->addressActions,
            $this->updateShippingPrice,
            $this->defaultShippingMethodSetter
        );
    }

    public function testExecuteWithInvalidEntity(): void
    {
        $data = new ActionData();
        $data->offsetSet('data', new \stdClass());

        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Only Checkout entity is supported');

        $this->operation->execute($data);
    }

    public function testExecuteWithValidEntity(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setBillingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('visitor_email', 'test@example.com');
        $data->offsetSet('save_address', true);
        $data->offsetSet('ship_to_billing_address', true);

        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->set('state_token', 'sample_token');

        $this->workflowManager->expects(self::once())
            ->method('getFirstWorkflowItemByEntity')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->actionExecutor->expects(self::once())
            ->method('executeActionGroup')
            ->with('update_checkout_state', [
                'checkout' => $checkout,
                'state_token' => $workflowItem->getData()->get('state_token'),
                'update_checkout_state' => true,
            ]);

        $this->actionExecutor->expects(self::exactly(3))
            ->method('executeAction')
            ->withConsecutive(
                ['flush_entity', [$workflowItem]],
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->addressActions->expects(self::once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveBillingAddress());
        self::assertTrue($checkout->isShipToBillingAddress());
        self::assertNull($checkout->getShippingMethod());
        self::assertNotNull($workflowItem->getUpdated());
    }

    public function testExecuteWithNullShippingCost(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setShippingCost(Price::create(10, 'USD'));
        $checkout->setBillingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('visitor_email', 'test@example.com');
        $data->offsetSet('save_address', true);

        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->set('state_token', 'sample_token');

        $this->workflowManager->expects(self::once())
            ->method('getFirstWorkflowItemByEntity')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->actionExecutor->expects(self::once())
            ->method('executeActionGroup')
            ->with('update_checkout_state', [
                'checkout' => $checkout,
                'state_token' => $workflowItem->getData()->get('state_token'),
                'update_checkout_state' => true,
            ]);

        $this->actionExecutor->expects(self::exactly(3))
            ->method('executeAction')
            ->withConsecutive(
                ['flush_entity', [$workflowItem]],
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->addressActions->expects(self::once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveBillingAddress());
        self::assertFalse($checkout->isShipToBillingAddress());
        self::assertEquals('test_method', $checkout->getShippingMethod());
        self::assertNotNull($workflowItem->getUpdated());
    }

    public function testExecuteWithOldAddress(): void
    {
        $data = new ActionData();
        $address = new OrderAddress();
        $oldAddress = new OrderAddress();
        ReflectionUtil::setId($oldAddress, 42);

        $checkout = $this->getEntity(Checkout::class);
        $checkout->setShippingMethod('test_method');
        $checkout->setShippingCost(Price::create(10, 'USD'));
        $checkout->setBillingAddress($address);

        $data->offsetSet('data', $checkout);
        $data->offsetSet('visitor_email', 'test@example.com');
        $data->offsetSet('save_address', true);
        $data->offsetSet('ship_to_billing_address', true);
        $data->offsetSet('oldAddress', $oldAddress);

        $workflowItem = new WorkflowItem();
        $workflowItem->getData()->set('state_token', 'sample_token');

        $this->workflowManager->expects(self::once())
            ->method('getFirstWorkflowItemByEntity')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->actionExecutor->expects(self::once())
            ->method('executeActionGroup')
            ->with('update_checkout_state', [
                'checkout' => $checkout,
                'state_token' => $workflowItem->getData()->get('state_token'),
                'update_checkout_state' => true,
            ]);

        $this->actionExecutor->expects(self::exactly(4))
            ->method('executeAction')
            ->withConsecutive(
                ['flush_entity', [$workflowItem]],
                ['remove_entity', [$oldAddress]],
                ['flush_entity', [$address]],
                ['flush_entity', [$checkout]]
            );

        $this->addressActions->expects(self::once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects(self::never())
            ->method('setDefaultShippingMethod');

        $this->operation->execute($data);

        self::assertTrue($checkout->isSaveBillingAddress());
        self::assertTrue($checkout->isShipToBillingAddress());
        self::assertEquals('test_method', $checkout->getShippingMethod());
        self::assertNotNull($workflowItem->getUpdated());
    }
}
