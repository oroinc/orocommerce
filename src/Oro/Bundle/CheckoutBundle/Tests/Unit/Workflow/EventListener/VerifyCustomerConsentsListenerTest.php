<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\VerifyCustomerConsentsListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\Transition\StepEnteredEvent;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class VerifyCustomerConsentsListenerTest extends TestCase
{
    private MockObject|ActionExecutor $actionExecutor;
    private MockObject|CheckoutWorkflowHelper $checkoutWorkflowHelper;
    private MockObject|WorkflowManager $workflowManager;
    private MockObject|TokenStorageInterface $tokenStorage;
    private FeatureChecker|MockObject $featureChecker;

    private VerifyCustomerConsentsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new VerifyCustomerConsentsListener(
            $this->actionExecutor,
            $this->checkoutWorkflowHelper,
            $this->workflowManager,
            $this->tokenStorage
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
    }

    public function testCheckConsentsWhenFeaturesDisabled(): void
    {
        $this->assertFeatureCheckCall(false);

        $event = $this->createMock(CheckoutRequestEvent::class);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenNotApplicableNoCurrentStepGuest(): void
    {
        $this->assertFeatureCheckCall(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $this->assertNoCurrentStepWithGuestCheck($workflowItem, true);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenNotApplicableNoSuchWorkflow(): void
    {
        $this->assertFeatureCheckCall(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $currentStep = new WorkflowStep();
        $currentStep->setName('step1');
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn(null);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenNotApplicableSameDestinationStep(): void
    {
        $this->assertFeatureCheckCall(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $currentStep = new WorkflowStep();
        $currentStep->setName('step1');
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $stepTo = new Step();
        $stepTo->setName('step1');
        $verifyTransition = $this->createMock(Transition::class);
        $verifyTransition->expects($this->once())
            ->method('getStepTo')
            ->willReturn($stepTo);
        $workflow = $this->createMock(Workflow::class);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->with('verify_customer_consents')
            ->willReturn($verifyTransition);
        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenNotApplicableTransitionNotAllowed(): void
    {
        $this->assertFeatureCheckCall(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $currentStep = new WorkflowStep();
        $currentStep->setName('step1');
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $stepTo = new Step();
        $stepTo->setName('step2');
        $verifyTransition = $this->createMock(Transition::class);
        $verifyTransition->expects($this->once())
            ->method('getStepTo')
            ->willReturn($stepTo);
        $workflow = $this->createMock(Workflow::class);
        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->with('verify_customer_consents')
            ->willReturn($verifyTransition);

        $currentStepModel = $this->createMock(Step::class);
        $currentStepModel->expects($this->once())
            ->method('isAllowedTransition')
            ->with('verify_customer_consents')
            ->willReturn(false);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())
            ->method('getStep')
            ->with('step1')
            ->willReturn($currentStepModel);

        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn($stepManager);

        $this->workflowManager->expects($this->any())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenAlreadyAccepted(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->assertFeatureCheckCall(true);
        $this->assertNoCurrentStepWithGuestCheck($workflowItem, false);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $result = new \ArrayObject(['isConsentsAccepted' => false]);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $data = new WorkflowData(['customerConsents' => []]);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_consents_accepted',
                ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
            )
            ->willReturn(true);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->listener->checkConsents($event);

        $this->assertTrue($result->offsetGet('isConsentsAccepted'));
    }

    public function testCheckConsentsWhenNotAcceptedAndConsentsAvailable(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->assertFeatureCheckCall(true);
        $this->assertNoCurrentStepWithGuestCheck($workflowItem, false);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $result = new \ArrayObject(['isConsentsAccepted' => false]);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $data = new WorkflowData(['customerConsents' => [], 'consents_available' => true]);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_consents_accepted',
                ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
            )
            ->willReturn(false);
        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'type' => 'warning',
                    'message' => 'oro.checkout.workflow.condition.required_consents_should_be_checked.message'
                ]
            );

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'verify_customer_consents');

        $event->expects($this->never())
            ->method('setWorkflowStep');
        $event->expects($this->never())
            ->method('stopPropagation');

        $this->listener->checkConsents($event);
    }

    public function testCheckConsentsWhenNotAcceptedAndConsentsNotAvailable(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->assertFeatureCheckCall(true);

        $currentStep = new WorkflowStep();
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturnOnConsecutiveCalls(
                null,
                $currentStep
            );

        $customerUser = new CustomerUser();
        $customerUser->setIsGuest(false);
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $event = $this->createMock(CheckoutRequestEvent::class);
        $event->expects($this->once())
            ->method('getCheckout')
            ->willReturn($this->createMock(Checkout::class));

        $result = new \ArrayObject(['isConsentsAccepted' => false]);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $data = new WorkflowData(['customerConsents' => [], 'consents_available' => false]);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_consents_accepted',
                ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
            )
            ->willReturn(false);
        $this->actionExecutor->expects($this->never())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'type' => 'warning',
                    'message' => 'oro.checkout.workflow.condition.required_consents_should_be_checked.message'
                ]
            );

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'verify_customer_consents');

        $event->expects($this->once())
            ->method('setWorkflowStep')
            ->with($currentStep);
        $event->expects($this->once())
            ->method('stopPropagation');

        $this->listener->checkConsents($event);
    }

    public function testOnStepEnteredWhenNotCustomerConsentsStep(): void
    {
        $currentStep = new WorkflowStep();
        $currentStep->setName('step1');

        $data = new WorkflowData([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $event = new StepEnteredEvent($workflowItem, $this->createMock(Transition::class));

        $this->listener->onStepEntered($event);
        $this->assertNull($data->offsetGet('consents_available'));
    }

    public function testOnStepEnteredWhenNotCheckout(): void
    {
        $currentStep = new WorkflowStep();
        $currentStep->setName('customer_consents');

        $data = new WorkflowData([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->once())
            ->method('getExclusiveRecordGroups')
            ->willReturn([]);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $event = new StepEnteredEvent($workflowItem, $this->createMock(Transition::class));

        $this->listener->onStepEntered($event);
        $this->assertNull($data->offsetGet('consents_available'));
    }

    public function testOnStepEntered(): void
    {
        $currentStep = new WorkflowStep();
        $currentStep->setName('customer_consents');

        $data = new WorkflowData([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($currentStep);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->once())
            ->method('getExclusiveRecordGroups')
            ->willReturn(['b2b_checkout_flow']);
        $workflowItem->expects($this->any())
            ->method('getDefinition')
            ->willReturn($definition);

        $event = new StepEnteredEvent($workflowItem, $this->createMock(Transition::class));

        $this->listener->onStepEntered($event);
        $this->assertTrue($data->offsetGet('consents_available'));
    }

    private function assertFeatureCheckCall(bool $isEnabled): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn($isEnabled);
    }

    private function assertNoCurrentStepWithGuestCheck(MockObject|WorkflowItem $workflowItem, bool $isGuest): void
    {
        $workflowItem->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn(null);

        $customerUser = new CustomerUser();
        $customerUser->setIsGuest($isGuest);
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);
    }
}
