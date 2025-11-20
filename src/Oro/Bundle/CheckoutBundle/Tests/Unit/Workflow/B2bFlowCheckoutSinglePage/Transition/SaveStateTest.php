<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\SaveState;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use PHPUnit\Framework\TestCase;

class SaveStateTest extends TestCase
{
    private ActionExecutor $actionExecutor;
    private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter;
    private TransitionServiceInterface $baseTransition;
    private SaveState $saveState;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);

        $this->saveState = new SaveState(
            $this->actionExecutor,
            $this->defaultShippingMethodSetter,
            $this->baseTransition
        );
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowData = new WorkflowData();
        $workflowResult = new WorkflowResult();

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($workflowData);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($workflowResult);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('is_consents_accepted', ['acceptedConsents' => null])
            ->willReturn(false);

        $checkout->expects($this->once())
            ->method('getShippingCost')
            ->willReturn(null);

        $checkout->expects($this->once())
            ->method('setShippingMethod')
            ->with(null);

        $this->defaultShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->saveState->execute($workflowItem);

        $this->assertTrue($workflowData->offsetGet('consents_available'));
        $this->assertTrue($workflowResult->offsetGet('responseData')['stateSaved']);
    }
}
