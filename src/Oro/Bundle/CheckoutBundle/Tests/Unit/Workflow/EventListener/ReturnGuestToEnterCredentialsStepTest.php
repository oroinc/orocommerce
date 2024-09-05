<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\ReturnGuestToEnterCredentialsStep;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReturnGuestToEnterCredentialsStepTest extends TestCase
{
    private WorkflowManager|MockObject $workflowManager;
    private ReturnGuestToEnterCredentialsStep $listener;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->listener = new ReturnGuestToEnterCredentialsStep($this->workflowManager);
    }

    public function testOnCompleteWhenNoBackToLoginTransition(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowDefinition = new WorkflowDefinition();
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->never())
            ->method('isStart');

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWhenTransitionIsNotStart(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(false);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWhenNoCurrentStep(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn(null);
        $transition = $this->prepareStartTransition();

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWhenCurrentStepIsLoginStep(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);

        $this->assertGetCurrentStepCall($workflowItem);
        $transition = $this->prepareStartTransition();

        $workflow = $this->createMock(Workflow::class);
        $this->assertTransitionManagerCall($workflow, 'step1');

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWhenCheckoutIsNotGuest(): void
    {
        $checkout = new Checkout();
        $customerUser = new CustomerUser();
        $customerUser->setIsGuest(false);
        $checkout->setCustomerUser($customerUser);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);
        $this->assertGetCurrentStepCall($workflowItem);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $transition = $this->prepareStartTransition();
        $workflow = $this->createMock(Workflow::class);

        $this->assertTransitionManagerCall($workflow, 'step2');
        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteWhenCheckoutTransitionIsNotAllowed(): void
    {
        $checkout = new Checkout();
        $customerUser = new CustomerUser();
        $customerUser->setIsGuest(true);
        $checkout->setCustomerUser($customerUser);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);
        $this->assertGetCurrentStepCall($workflowItem);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $transition = $this->prepareStartTransition();
        $workflow = $this->createMock(Workflow::class);

        $this->assertTransitionManagerCall($workflow, 'step2');
        $this->assertStepManagerCall($workflow, false);

        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    public function testOnCompleteTransitsGuestToLogin(): void
    {
        $checkout = new Checkout();
        $customerUser = new CustomerUser();
        $customerUser->setIsGuest(true);
        $checkout->setCustomerUser($customerUser);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->assertDefinitionMetadataCall($workflowItem);
        $this->assertGetCurrentStepCall($workflowItem);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $transition = $this->prepareStartTransition();
        $workflow = $this->createMock(Workflow::class);

        $backTransition = $this->assertTransitionManagerCall($workflow, 'step2');
        $this->assertStepManagerCall($workflow, true);

        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($workflowItem, $backTransition);

        $event = new TransitionEvent($workflowItem, $transition);

        $this->listener->onComplete($event);
    }

    private function prepareStartTransition(): Transition|MockObject
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        return $transition;
    }

    private function assertDefinitionMetadataCall(WorkflowItem|MockObject $workflowItem): void
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setMetadata(['guest_checkout' => ['return_to_login_transition' => 'back_to_login']]);
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
    }

    private function assertGetCurrentStepCall(WorkflowItem|MockObject $workflowItem): void
    {
        $step = new WorkflowStep();
        $step->setName('step1');
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($step);
    }

    private function assertTransitionManagerCall(
        MockObject|Workflow $workflow,
        string $stepName
    ): Transition|MockObject {
        $backStep = new WorkflowStep();
        $backStep->setName($stepName);
        $backTransition = $this->createMock(Transition::class);
        $backTransition->expects($this->any())
            ->method('getStepTo')
            ->willReturn($backStep);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->any())
            ->method('getTransition')
            ->with('back_to_login')
            ->willReturn($backTransition);
        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        return $backTransition;
    }

    private function assertStepManagerCall(MockObject|Workflow $workflow, bool $isTransitionAllowed): void
    {
        $currentStep = $this->createMock(Step::class);
        $currentStep->expects($this->any())
            ->method('isAllowedTransition')
            ->with('back_to_login')
            ->willReturn($isTransitionAllowed);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())
            ->method('getStep')
            ->with('step1')
            ->willReturn($currentStep);
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn($stepManager);
    }
}
