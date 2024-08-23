<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\EventListener\Workflow;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\EventListener\Workflow\CheckoutStateListener;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Event\Transition\GuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutStateListenerTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutStateDiffManager|MockObject $checkoutStateDiffManager;
    private UpdateCheckoutStateInterface|MockObject $updateCheckoutStateAction;
    private CheckoutDiffStorageInterface|MockObject $diffStorage;

    private CheckoutStateListener $listener;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->checkoutStateDiffManager = $this->createMock(CheckoutStateDiffManager::class);
        $this->updateCheckoutStateAction = $this->createMock(UpdateCheckoutStateInterface::class);
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);

        $this->listener = new CheckoutStateListener(
            $this->actionExecutor,
            $this->checkoutStateDiffManager,
            $this->updateCheckoutStateAction,
            $this->diffStorage
        );
    }

    public function testInitializeCurrentCheckoutState()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData(new WorkflowData());

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $event = new WorkflowItemAwareEvent($workflowItem);

        $this->checkoutStateDiffManager->expects($this->once())
            ->method('getCurrentState')
            ->with($checkout)
            ->willReturn(['some_state']);

        $this->listener->initializeCurrentCheckoutState($event);
    }

    public function testInitializeCurrentCheckoutStateProtectionDisabled()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData(new WorkflowData());

        $metadata = [];
        $this->configureMetadata($metadata, $workflowItem);

        $event = new WorkflowItemAwareEvent($workflowItem);

        $this->checkoutStateDiffManager->expects($this->never())
            ->method('getCurrentState');

        $this->listener->initializeCurrentCheckoutState($event);
    }

    public function testOnFormInit()
    {
        $checkout = new Checkout();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true],
            'is_checkout_workflow' => true
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_continue' => true]);

        $this->updateCheckoutStateAction->expects($this->once())
            ->method('execute')
            ->with($checkout, 'token111', false, false)
            ->willReturn(true);

        $this->listener->onFormInit($event);
        $this->assertTrue($workflowItem->getResult()->offsetGet('updateCheckoutState'));
    }

    public function testOnFormInitNotContinueTransition()
    {
        $checkout = new Checkout();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true],
            'is_checkout_workflow' => true
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);

        $this->updateCheckoutStateAction->expects($this->never())
            ->method('execute');

        $this->listener->onFormInit($event);
        $this->assertNull($workflowItem->getResult()->offsetGet('updateCheckoutState'));
    }

    public function testUpdateCheckoutState()
    {
        $checkout = new Checkout();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $transition->expects($this->once())
            ->method('getName')
            ->willReturn('some_transition');

        $metadata = [
            'checkout_state_config' => [
                'enable_state_protection' => true,
                'additionally_update_state_after' => ['some_transition']
            ]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->updateCheckoutStateAction->expects($this->once())
            ->method('execute')
            ->with($checkout, 'token111', false, true)
            ->willReturn(true);

        $this->listener->updateCheckoutState($event);
        $this->assertTrue($workflowItem->getResult()->offsetGet('updateCheckoutState'));
    }

    public function testUpdateCheckoutStateNotSupportedTransition()
    {
        $checkout = new Checkout();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $transition->expects($this->once())
            ->method('getName')
            ->willReturn('some_other_transition');

        $metadata = [
            'checkout_state_config' => [
                'enable_state_protection' => true,
                'additionally_update_state_after' => ['some_transition']
            ]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->updateCheckoutStateAction->expects($this->never())
            ->method('execute');

        $this->listener->updateCheckoutState($event);
        $this->assertNull($workflowItem->getResult()->offsetGet('updateCheckoutState'));
    }

    public function testOnPreGuard()
    {
        $errors = new ArrayCollection();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new GuardEvent($workflowItem, $transition, true, $errors);

        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_continue' => true]);
        $transition->expects($this->any())
            ->method('isHidden')
            ->willReturn(false);
        $transition->expects($this->once())
            ->method('getName')
            ->willReturn('some_transition');

        $metadata = [
            'checkout_state_config' => [
                'enable_state_protection' => true,
                'protect_transitions' => ['some_transition']
            ]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with(
                'is_checkout_state_valid',
                [
                    'entity' => $checkout,
                    'token' => 'token111',
                    'current_state' => null
                ],
                $errors,
                'oro.checkout.workflow.condition.content_of_order_was_changed.message'
            )
            ->willReturn(true);

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    /**
     * @dataProvider preGueardSkipDataProvider
     */
    public function testOnPreGuardSkipped(
        bool $isAllowed,
        bool $isProtectionEnabled,
        bool $isTransitionCheckoutContinue,
        bool $isTransitionHidden,
        string $transitionName
    ) {
        $errors = new ArrayCollection();
        $data = new WorkflowData([
            'state_token' => 'token111'
        ]);
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new GuardEvent($workflowItem, $transition, $isAllowed, $errors);

        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_continue' => $isTransitionCheckoutContinue]);
        $transition->expects($this->any())
            ->method('isHidden')
            ->willReturn($isTransitionHidden);
        $transition->expects($this->any())
            ->method('getName')
            ->willReturn($transitionName);

        $metadata = [
            'checkout_state_config' => [
                'enable_state_protection' => $isProtectionEnabled,
                'protect_transitions' => ['some_transition']
            ]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->actionExecutor->expects($this->never())
            ->method('evaluateExpression');

        $this->listener->onPreGuard($event);
    }

    public static function preGueardSkipDataProvider(): array
    {
        return [
            [
                'isAllowed' => false,
                'isProtectionEnabled' => true,
                'isTransitionCheckoutContinue' => true,
                'isTransitionHidden' => false,
                'transitionName' => 'some_transition'
            ],
            [
                'isAllowed' => true,
                'isProtectionEnabled' => false,
                'isTransitionCheckoutContinue' => true,
                'isTransitionHidden' => false,
                'transitionName' => 'some_transition'
            ],
            [
                'isAllowed' => true,
                'isProtectionEnabled' => true,
                'isTransitionCheckoutContinue' => false,
                'isTransitionHidden' => false,
                'transitionName' => 'some_transition'
            ],
            [
                'isAllowed' => true,
                'isProtectionEnabled' => true,
                'isTransitionCheckoutContinue' => true,
                'isTransitionHidden' => false,
                'transitionName' => 'some_other_transition'
            ]
        ];
    }

    public function testUpdateStateTokenSinglePageCheckout()
    {
        $data = new WorkflowData(['state_token' => 'token111']);
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($data);
        $currentStep = new WorkflowStep();
        $currentStep->setFinal(false);
        $workflowItem->setCurrentStep($currentStep);
        $event = new WorkflowItemAwareEvent($workflowItem);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true],
            'is_checkout_workflow' => true,
            'is_single_page_checkout' => true
        ];
        $this->configureMetadata($metadata, $workflowItem);
        $this->listener->updateStateTokenSinglePageCheckout($event);
        $this->assertNotEmpty($data->offsetGet('state_token'));
        $this->assertNotEquals('token111', $data->offsetGet('state_token'));
    }

    /**
     * @dataProvider updateSinglePageCheckoutDataProvider
     */
    public function testUpdateStateTokenSinglePageCheckoutNotUpdated(
        bool $isProtectionEnabled,
        bool $isCheckoutWorkflow,
        bool $isSinglePageCheckout,
        bool $isFinalStep
    ) {
        $data = new WorkflowData(['state_token' => 'token111']);
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($data);
        $currentStep = new WorkflowStep();
        $currentStep->setFinal($isFinalStep);
        $workflowItem->setCurrentStep($currentStep);
        $event = new WorkflowItemAwareEvent($workflowItem);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => $isProtectionEnabled],
            'is_checkout_workflow' => $isCheckoutWorkflow,
            'is_single_page_checkout' => $isSinglePageCheckout
        ];
        $this->configureMetadata($metadata, $workflowItem);
        $this->listener->updateStateTokenSinglePageCheckout($event);
        $this->assertNotEmpty($data->offsetGet('state_token'));
        $this->assertEquals('token111', $data->offsetGet('state_token'));
    }

    public static function updateSinglePageCheckoutDataProvider(): array
    {
        return [
            [false, true, true, false],
            [true, false, true, false],
            [true, true, false, false],
            [true, true, true, true]
        ];
    }

    public function testUpdateStateTokenMultiPageCheckout()
    {
        $data = new WorkflowData(['state_token' => 'token111']);
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($data);
        $currentStep = new WorkflowStep();
        $currentStep->setFinal(false);
        $workflowItem->setCurrentStep($currentStep);
        $event = new WorkflowItemAwareEvent($workflowItem);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true],
            'is_checkout_workflow' => true,
            'is_single_page_checkout' => false
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->listener->updateStateTokenMultiPageCheckout($event);
        $this->assertNotEmpty($data->offsetGet('state_token'));
        $this->assertNotEquals('token111', $data->offsetGet('state_token'));
    }

    /**
     * @dataProvider updateMultiStepCheckoutDataProvider
     */
    public function testUpdateStateTokenMultiPageCheckoutNotUpdated(
        bool $isProtectionEnabled,
        bool $isCheckoutWorkflow,
        bool $isSinglePageCheckout,
        bool $isFinalStep
    ) {
        $data = new WorkflowData(['state_token' => 'token111']);
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($data);
        $currentStep = new WorkflowStep();
        $currentStep->setFinal($isFinalStep);
        $workflowItem->setCurrentStep($currentStep);
        $event = new WorkflowItemAwareEvent($workflowItem);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => $isProtectionEnabled],
            'is_checkout_workflow' => $isCheckoutWorkflow,
            'is_single_page_checkout' => $isSinglePageCheckout
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->listener->updateStateTokenMultiPageCheckout($event);
        $this->assertNotEmpty($data->offsetGet('state_token'));
        $this->assertEquals('token111', $data->offsetGet('state_token'));
    }

    public static function updateMultiStepCheckoutDataProvider(): array
    {
        return [
            [false, true, false, false],
            [true, false, false, false],
            [true, true, true, false],
            [true, true, false, true]
        ];
    }

    public function testDeleteCheckoutState()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData(new WorkflowData());

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $event = new WorkflowItemAwareEvent($workflowItem);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($checkout);

        $this->listener->deleteCheckoutState($event);
    }

    public function testDeleteCheckoutStateDisabled()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData(new WorkflowData());

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => false]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $event = new WorkflowItemAwareEvent($workflowItem);

        $this->diffStorage->expects($this->never())
            ->method('deleteStates');

        $this->listener->deleteCheckoutState($event);
    }

    public function testDeleteCheckoutStateOnStart()
    {
        $data = new WorkflowData(['state_token' => 'token111']);
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $transition->expects($this->once())
            ->method('isStart')
            ->willReturn(true);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => true]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->diffStorage->expects($this->once())
            ->method('deleteStates')
            ->with($checkout, 'token111');

        $this->listener->deleteCheckoutStateOnStart($event);
    }

    /**
     * @dataProvider deleteOnStartProtectionEnabled
     */
    public function testDeleteCheckoutStateOnStartNotCalled(
        bool $isProtectionEnabled,
        bool $isStartTransition,
        ?string $stateToken
    ) {
        $data = new WorkflowData(['state_token' => $stateToken]);
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setData($data);
        $transition = $this->createMock(Transition::class);
        $event = new TransitionEvent($workflowItem, $transition);

        $transition->expects($this->any())
            ->method('isStart')
            ->willReturn($isStartTransition);

        $metadata = [
            'checkout_state_config' => ['enable_state_protection' => $isProtectionEnabled]
        ];
        $this->configureMetadata($metadata, $workflowItem);

        $this->diffStorage->expects($this->never())
            ->method('deleteStates');

        $this->listener->deleteCheckoutStateOnStart($event);
    }

    public static function deleteOnStartProtectionEnabled(): array
    {
        return [
            [false, true, 'token111'],
            [true, false, 'token111'],
            [true, true, null],
        ];
    }

    private function configureMetadata(array $metadata, WorkflowItem $workflowItem): void
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $workflowItem->setDefinition($definition);
    }
}
