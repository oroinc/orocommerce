<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutState;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateCheckoutStateTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutDiffStorageInterface|MockObject $diffStorage;
    private CheckoutStateDiffManager|MockObject $diffManager;

    private UpdateCheckoutState $updateCheckoutState;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->diffManager = $this->createMock(CheckoutStateDiffManager::class);

        $this->updateCheckoutState = new UpdateCheckoutState(
            $this->actionExecutor,
            $this->diffStorage,
            $this->diffManager
        );
    }

    public function testExecuteWithSupportedRequest(): void
    {
        $checkout = new Checkout();
        $stateToken = 'some_token';

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('check_request', ['expected_key' => 'update_checkout_state', 'expected_value' => 1])
            ->willReturn(true);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($checkout, $stateToken);

        $this->diffManager->expects($this->once())
            ->method('getCurrentState')
            ->with($checkout)
            ->willReturn(['current_state_data']);

        $this->diffStorage->expects($this->once())
            ->method('addState')
            ->with($checkout, ['current_state_data'], ['token' => $stateToken]);

        $result = $this->updateCheckoutState->execute($checkout, $stateToken, false, false);

        $this->assertFalse($result);
    }

    public function testExecuteWithNonSupportedRequestAndExistingState(): void
    {
        $checkout = new Checkout();
        $stateToken = 'some_token';
        $savedCheckoutState = ['some_state_data'];

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('check_request', ['expected_key' => 'update_checkout_state', 'expected_value' => 1])
            ->willReturn(false);

        $this->diffStorage->expects($this->once())
            ->method('getState')
            ->with($checkout, $stateToken)
            ->willReturn($savedCheckoutState);

        $this->diffStorage->expects($this->never())
            ->method('deleteStates');

        $this->diffManager->expects($this->never())
            ->method('getCurrentState');

        $this->diffStorage->expects($this->never())
            ->method('addState');

        $result = $this->updateCheckoutState->execute($checkout, $stateToken, false, false);

        $this->assertFalse($result);
    }

    public function testExecuteWithForceUpdate(): void
    {
        $checkout = new Checkout();
        $stateToken = 'some_token';

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('check_request', ['expected_key' => 'update_checkout_state', 'expected_value' => 1])
            ->willReturn(false);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($checkout, $stateToken);

        $this->diffManager->expects($this->once())
            ->method('getCurrentState')
            ->with($checkout)
            ->willReturn(['current_state_data']);

        $this->diffStorage->expects($this->once())
            ->method('addState')
            ->with($checkout, ['current_state_data'], ['token' => $stateToken]);

        $result = $this->updateCheckoutState->execute($checkout, $stateToken, true, false);

        $this->assertFalse($result);
    }
}
