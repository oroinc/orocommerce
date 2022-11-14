<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CustomerBundle\Handler\CustomerRegistrationHandler;
use Oro\Bundle\CustomerBundle\Handler\ForgotPasswordHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckoutWorkflowHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

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
            $registrationHandler,
            $forgotPasswordHandler,
            $eventDispatcher,
            $translator
        );
    }

    /**
     * @dataProvider workflowItemProvider
     */
    public function testGetWorkflowItem(array $items, bool $expectException, WorkflowItem $expected)
    {
        $checkout = $this->createMock(Checkout::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        if ($expectException) {
            $this->expectException(NotFoundHttpException::class);
            $this->expectExceptionMessage('Unable to find correct WorkflowItem for current checkout');
        }
        $result = $this->helper->getWorkflowItem($checkout);
        $this->assertEquals($expected, $result);
    }

    public function testProcessWorkflowAndGetCurrentStepWhenRequestedLayoutUpdates()
    {
        $checkout = $this->createMock(Checkout::class);
        $request = Request::create(
            'checkout/1',
            Request::METHOD_GET,
            [
                'transition' => 'payment_error',
                'layout_block_ids' => ['some_block']
            ],
        );
        $actionGroup = $this->createMock(ActionGroup::class);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute');

        $items = [
            $this->getEntity(WorkflowItem::class, ['id' => 1]),
        ];
        $this->workflowManager->expects($this->once())
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
        $checkout = $this->createMock(Checkout::class);
        $request = Request::create(
            'checkout/1',
            Request::METHOD_GET,
            [
                'transition' => 'payment_error',
            ],
        );
        $actionGroup = $this->createMock(ActionGroup::class);
        $this->actionGroupRegistry->expects(self::once())
            ->method('findByName')
            ->willReturn($actionGroup);
        $actionGroup->expects(self::once())
            ->method('execute');

        $items = [
            $this->getEntity(WorkflowItem::class, ['id' => 1]),
        ];
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn($items);

        $this->workflowManager->expects(self::any())
            ->method('getTransitionsByWorkflowItem')
            ->willReturn([]);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed');

        $this->helper->processWorkflowAndGetCurrentStep($request, $checkout);
    }

    public function workflowItemProvider(): array
    {
        return [
            'Items count equals one' => [
                'items' => [
                    $this->getEntity(WorkflowItem::class, ['id' => 1])
                ],
                'expectException' => false,
                'expectedResult' => $this->getEntity(WorkflowItem::class, ['id' => 1])
            ],
            'Items count more than one' => [
                'items' => [
                    $this->getEntity(WorkflowItem::class, ['id' => 1]),
                    $this->getEntity(WorkflowItem::class, ['id' => 2]),
                ],
                'expectException' => true,
                'expectedResult' => $this->getEntity(WorkflowItem::class, ['id' => 1])
            ]
        ];
    }
}
