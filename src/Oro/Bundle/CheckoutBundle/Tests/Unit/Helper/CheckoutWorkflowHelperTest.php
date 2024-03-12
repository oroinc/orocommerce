<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutLineItemGroupingInvalidationHelper;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CustomerBundle\Handler\CustomerRegistrationHandler;
use Oro\Bundle\CustomerBundle\Handler\ForgotPasswordHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutWorkflowHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var CheckoutLineItemGroupingInvalidationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemGroupingInvalidationHelper;

    /** @var ActionGroupRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $actionGroupRegistry;

    /** @var CheckoutWorkflowHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);
        $transitionProvider = $this->createMock(TransitionProvider::class);
        $transitionFormProvider = $this->createMock(TransitionFormProvider::class);
        $errorHandler = $this->createMock(CheckoutErrorHandler::class);
        $lineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutLineItemGroupingInvalidationHelper = $this->createMock(
            CheckoutLineItemGroupingInvalidationHelper::class
        );
        $registrationHandler = $this->createMock(CustomerRegistrationHandler::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $forgotPasswordHandler = $this->createMock(ForgotPasswordHandler::class);

        $this->helper = new CheckoutWorkflowHelper(
            $this->workflowManager,
            $this->actionGroupRegistry,
            $transitionProvider,
            $transitionFormProvider,
            $errorHandler,
            $lineItemsManager,
            $this->checkoutLineItemGroupingInvalidationHelper,
            $registrationHandler,
            $forgotPasswordHandler,
            $eventDispatcher,
            $translator
        );
    }

    private function getCheckout(int $id = 1): Checkout
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, $id);

        return $checkout;
    }

    private function getWorkflowItem(int $id): WorkflowItem
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setId($id);
        $workflowItem->setCurrentStep(new WorkflowStep());

        return $workflowItem;
    }

    public function testGetWorkflowItemWhenOneWorkflowItemFound()
    {
        $workflowItem = $this->getWorkflowItem(1);

        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$workflowItem]);

        self::assertSame($workflowItem, $this->helper->getWorkflowItem($checkout));
    }

    public function testGetWorkflowItemWhenSeveralWorkflowItemsFound()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find correct WorkflowItem for current checkout');

        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$this->getWorkflowItem(1), $this->getWorkflowItem(2)]);

        $this->helper->getWorkflowItem($checkout);
    }

    public function testGetWorkflowItemWhenNoWorkflowItemsFound()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find correct WorkflowItem for current checkout');

        $checkout = $this->getCheckout();
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([]);

        $this->helper->getWorkflowItem($checkout);
    }

    public function testFindWorkflowItems()
    {
        $workflowItems = [$this->getWorkflowItem(1), $this->getWorkflowItem(2)];
        $anotherWorkflowItems = [$this->getWorkflowItem(3)];

        $checkout = $this->getCheckout(10);
        $anotherCheckout = $this->getCheckout(20);
        $this->workflowManager->expects(self::exactly(2))
            ->method('getWorkflowItemsByEntity')
            ->withConsecutive([$checkout], [$anotherCheckout])
            ->willReturnOnConsecutiveCalls($workflowItems, $anotherWorkflowItems);

        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));
        // test memory cache
        self::assertSame($workflowItems, $this->helper->findWorkflowItems($checkout));
        self::assertSame($anotherWorkflowItems, $this->helper->findWorkflowItems($anotherCheckout));
    }

    public function testProcessWorkflowAndGetCurrentStepWhenRequestedLayoutUpdates()
    {
        $checkout = $this->getCheckout();
        $request = Request::create(
            'checkout/1',
            Request::METHOD_GET,
            [
                'transition' => 'payment_error',
                'layout_block_ids' => ['some_block']
            ]
        );
        $actionGroup = $this->createMock(ActionGroup::class);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute');

        $items = [$this->getWorkflowItem(1)];
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        $this->workflowManager->expects(self::never())
            ->method('transitIfAllowed');
        $this->workflowManager->expects(self::any())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([]);

        $this->helper->processWorkflowAndGetCurrentStep($request, $checkout);
    }

    public function testProcessWorkflowAndGetCurrentStep()
    {
        $checkout = $this->getCheckout();
        $request = Request::create(
            'checkout/1',
            Request::METHOD_GET,
            [
                'transition' => 'payment_error'
            ]
        );
        $actionGroup = $this->createMock(ActionGroup::class);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute');

        $items = [$this->getWorkflowItem(1)];
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        $this->workflowManager->expects(self::any())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([]);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed');

        $this->checkoutLineItemGroupingInvalidationHelper->expects(self::once())
            ->method('shouldInvalidateLineItemGrouping')
            ->with(reset($items))
            ->willReturn(false);

        $this->checkoutLineItemGroupingInvalidationHelper->expects(self::never())
            ->method('invalidateLineItemGrouping');

        $this->helper->processWorkflowAndGetCurrentStep($request, $checkout);
    }

    public function testProcessWorkflowAndGetCurrentStepWithLineItemGroupingInvalidation()
    {
        $checkout = $this->getCheckout();
        $request = Request::create(
            'checkout/1',
            Request::METHOD_GET,
            [
                'transition' => 'payment_error'
            ]
        );
        $actionGroup = $this->createMock(ActionGroup::class);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute');

        $items = [$this->getWorkflowItem(1)];
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        $this->workflowManager->expects(self::any())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([]);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed');

        $this->checkoutLineItemGroupingInvalidationHelper->expects(self::once())
            ->method('shouldInvalidateLineItemGrouping')
            ->with(reset($items))
            ->willReturn(true);

        $this->checkoutLineItemGroupingInvalidationHelper->expects(self::once())
            ->method('invalidateLineItemGrouping')
            ->with($checkout, reset($items));

        $this->helper->processWorkflowAndGetCurrentStep($request, $checkout);
    }
}
