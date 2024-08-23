<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\Handler\CheckoutPostRequestHandler;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CheckoutPostRequestHandlerTest extends TestCase
{
    private WorkflowManager|MockObject $workflowManager;
    private TransitionProvider|MockObject $transitionProvider;
    private TransitionFormProvider|MockObject $transitionFormProvider;
    private CheckoutErrorHandler|MockObject $errorHandler;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CheckoutPostRequestHandler $handler;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->transitionFormProvider = $this->createMock(TransitionFormProvider::class);
        $this->errorHandler = $this->createMock(CheckoutErrorHandler::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new CheckoutPostRequestHandler(
            $this->workflowManager,
            $this->transitionProvider,
            $this->transitionFormProvider,
            $this->errorHandler,
            $this->eventDispatcher
        );
    }

    public function testIsSupported()
    {
        $postRequest = $this->createMock(Request::class);
        $postRequest->method('isMethod')->with(Request::METHOD_POST)->willReturn(true);

        $getRequest = $this->createMock(Request::class);
        $getRequest->method('isMethod')->with(Request::METHOD_POST)->willReturn(false);

        $this->assertTrue($this->handler->isSupported($postRequest));
        $this->assertFalse($this->handler->isSupported($getRequest));
    }

    public function testHandleWithoutContinueTransition()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->getPreparedRequest(null);

        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn(null);

        $this->workflowManager->expects($this->never())
            ->method('transitUnconditionally');

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithNotContinueTransition()
    {
        $workflow = $this->createMock(Workflow::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->getPreparedRequest('valid_transaction');

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([]);

        $this->assertGetTransitionByName($workflowItem, $workflow, $transition);

        $this->workflowManager->expects($this->never())
            ->method('transitUnconditionally');

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithInvalidContinueTransition()
    {
        $workflow = $this->createMock(Workflow::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $request = $this->getPreparedRequest('valid_transaction');

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'is_checkout_continue' => true
            ]);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);
        $transitionManager = $this->createMock(TransitionManager::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $workflow->expects($this->any())
            ->method('checkTransitionValid')
            ->with($transition)
            ->willReturn(false);

        $this->workflowManager->expects($this->never())
            ->method('transitUnconditionally');

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithValidTransitionAndFormWhenTransitionNamePassed()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'is_checkout_continue' => true
            ]);
        $transitionForm = $this->createMock(FormInterface::class);
        $workflow = $this->createMock(Workflow::class);

        $transitionName = 'valid_transition';
        $request = $this->getPreparedRequest($transitionName);

        $this->assertGetTransitionByName($workflowItem, $workflow, $transition);

        $transitionForm->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $transitionForm->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $transitionForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->transitionFormProvider->expects($this->once())
            ->method('getTransitionFormByTransition')
            ->with($workflowItem, $transition)
            ->willReturn($transitionForm);

        $this->workflowManager->expects($this->once())
            ->method('transitUnconditionally')
            ->with($workflowItem, $transition);

        $errors = new FormErrorIterator($transitionForm, []);
        $transitionForm->expects($this->any())
            ->method('getErrors')
            ->willReturn($errors);
        $this->errorHandler->expects($this->never())
            ->method('addFlashWorkflowStateWarning');
        $this->errorHandler->expects($this->once())
            ->method('getWorkflowErrors')
            ->with($errors);

        $this->transitionProvider->expects($this->once())
            ->method('clearCache');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(CheckoutTransitionBeforeEvent::class), 'oro_checkout.transition_request.before'],
                [$this->isInstanceOf(CheckoutTransitionAfterEvent::class), 'oro_checkout.transition_request.after']
            );

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithValidTransitionAndFormWhenTransitionNameEmpty()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'is_checkout_continue' => true
            ]);
        $transitionForm = $this->createMock(FormInterface::class);

        $transitionName = null;
        $request = $this->getPreparedRequest($transitionName);

        $transitionData = new TransitionData($transition, true, new ArrayCollection([]));
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem)
            ->willReturn($transitionData);

        $transitionForm->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $transitionForm->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $transitionForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->transitionFormProvider->expects($this->once())
            ->method('getTransitionFormByTransition')
            ->with($workflowItem, $transition)
            ->willReturn($transitionForm);

        $this->workflowManager->expects($this->once())
            ->method('transitUnconditionally')
            ->with($workflowItem, $transition);

        $errors = new FormErrorIterator($transitionForm, []);
        $transitionForm->expects($this->any())
            ->method('getErrors')
            ->willReturn($errors);
        $this->errorHandler->expects($this->never())
            ->method('addFlashWorkflowStateWarning');
        $this->errorHandler->expects($this->once())
            ->method('getWorkflowErrors')
            ->with($errors);

        $this->transitionProvider->expects($this->once())
            ->method('clearCache');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(CheckoutTransitionBeforeEvent::class), 'oro_checkout.transition_request.before'],
                [$this->isInstanceOf(CheckoutTransitionAfterEvent::class), 'oro_checkout.transition_request.after']
            );

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithInvalidTransitionAndFormWhenTransitionNamePassed()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'is_checkout_continue' => true
            ]);
        $transitionForm = $this->createMock(FormInterface::class);
        $workflow = $this->createMock(Workflow::class);

        $transitionName = 'valid_transition';
        $request = $this->getPreparedRequest($transitionName);

        $this->assertGetTransitionByName($workflowItem, $workflow, $transition);

        $transitionForm->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $transitionForm->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $transitionForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $errors = new FormErrorIterator($transitionForm, [$this->createMock(FormError::class)]);
        $transitionForm->expects($this->any())
            ->method('getErrors')
            ->willReturn($errors);

        $this->transitionFormProvider->expects($this->once())
            ->method('getTransitionFormByTransition')
            ->with($workflowItem, $transition)
            ->willReturn($transitionForm);

        $this->workflowManager->expects($this->never())
            ->method('transitUnconditionally');

        $this->errorHandler->expects($this->once())
            ->method('addFlashWorkflowStateWarning')
            ->with($errors);
        $this->errorHandler->expects($this->once())
            ->method('getWorkflowErrors')
            ->with($errors);

        $this->transitionProvider->expects($this->once())
            ->method('clearCache');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(CheckoutTransitionBeforeEvent::class), 'oro_checkout.transition_request.before'],
                [$this->isInstanceOf(CheckoutTransitionAfterEvent::class), 'oro_checkout.transition_request.after']
            );

        $this->handler->handle($workflowItem, $request);
    }

    public function testHandleWithoutForm()
    {
        $workflow = $this->createMock(Workflow::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('getFrontendOptions')
            ->willReturn([
                'is_checkout_continue' => true
            ]);

        $transitionName = 'valid_transition';
        $request = $this->getPreparedRequest($transitionName);

        $this->assertGetTransitionByName($workflowItem, $workflow, $transition);

        $this->transitionFormProvider->expects($this->once())
            ->method('getTransitionFormByTransition')
            ->with($workflowItem, $transition)
            ->willReturn(null);

        $this->workflowManager->expects($this->once())
            ->method('isTransitionAvailable')
            ->with($workflowItem, $transition)
            ->willReturn(true);
        $this->workflowManager->expects($this->once())
            ->method('transitUnconditionally')
            ->with($workflowItem, $transition);

        $this->errorHandler->expects($this->never())
            ->method('addFlashWorkflowStateWarning');
        $this->errorHandler->expects($this->never())
            ->method('getWorkflowErrors');

        $this->transitionProvider->expects($this->once())
            ->method('clearCache');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(CheckoutTransitionBeforeEvent::class), 'oro_checkout.transition_request.before'],
                [$this->isInstanceOf(CheckoutTransitionAfterEvent::class), 'oro_checkout.transition_request.after']
            );

        $this->handler->handle($workflowItem, $request);
    }

    private function getPreparedRequest(?string $transitionName): Request|MockObject
    {
        $request = $this->createMock(Request::class);
        $request->query = $this->createMock(ParameterBag::class);
        $request->expects($this->any())
            ->method('get')
            ->with('transition')
            ->willReturn($transitionName);

        return $request;
    }

    private function assertGetTransitionByName(
        WorkflowItem $workflowItem,
        Workflow|MockObject $workflow,
        Transition $transition
    ): void {
        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);
        $transitionManager = $this->createMock(TransitionManager::class);
        $workflow->expects($this->once())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);
        $transitionManager->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $workflow->expects($this->any())
            ->method('checkTransitionValid')
            ->with($transition)
            ->willReturn(true);
    }
}
