<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\Purchase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Action\ExtendableAction;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PurchaseTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private PaymentMethodActionsInterface|MockObject $paymentMethodActions;
    private PaymentTransactionProvider|MockObject $paymentTransactionProvider;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private ValidatorInterface|MockObject $validator;

    private Purchase $purchase;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->paymentMethodActions = $this->createMock(PaymentMethodActionsInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->purchase = new Purchase(
            $this->actionExecutor,
            $this->checkoutActions,
            $this->paymentMethodActions,
            $this->paymentTransactionProvider,
            $this->baseContinueTransition
        );
        $this->purchase->setValidator($this->validator);
    }

    public function testIsConditionAllowedReturnsFalseWhenCheckoutIsCompleted(): void
    {
        $checkout = new Checkout();
        $checkout->setCompleted(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getEntity')
            ->willReturn($checkout);

        self::assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseWhenPaymentMethodDoesNotSupportValidate(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setCompleted(false);
        $checkout->setPaymentMethod('payment_method');
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects(self::once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(true);

        $this->paymentTransactionProvider->expects(self::once())
            ->method('getActiveValidatePaymentTransaction')
            ->with('payment_method')
            ->willReturn(null);

        self::assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseWhenPurchaseViaDirectUrlIsNotAllowed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setCompleted(false);
        $checkout->setPaymentMethod('payment_method');
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects(self::once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with('check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'])
            ->willReturn(true);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(false);

        self::assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testIsConditionAllowed(
        bool $isBasePreconditionAllowed,
        bool $isAllowedByValidators,
        bool $expected
    ): void {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects(self::once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn($isBasePreconditionAllowed);

        $violationsArray = [];
        if (!$isAllowedByValidators) {
            $violationsArray[] = $this->createMock(ConstraintViolationInterface::class);
        }
        $violations = new ConstraintViolationList($violationsArray);
        $this->validator->expects(self::any())
            ->method('validate')
            ->with($checkout, null, 'checkout_order_create_pre_checks')
            ->willReturn($violations);

        $this->actionExecutor->expects(self::any())
            ->method('evaluateExpression')
            ->willReturnMap([
                ['check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'], null, null, true]
            ]);

        self::assertSame($expected, $this->purchase->isConditionAllowed($workflowItem, $errors));
    }

    public static function conditionDataProvider(): array
    {
        return [
            'base precondition not allowed' => [false, true, false],
            'not allowed by validators' => [true, false, false],
            'allowed' => [true, true, true],
        ];
    }

    public function testExecuteSuccessful(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData([
            'order' => $order,
            'checkout' => $checkout,
            'additional_data' => 'additionalData',
            'email' => 'test@example.com',
        ]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'additionalData' => 'additionalData',
                    'email' => 'test@example.com'
                ]
            )
            ->willReturn(['responseData' => ['purchaseSuccessful' => true]]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => ['purchaseSuccessful' => true],
                        'email' => 'test@example.com'
                    ]
                ]
            );

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'finish_checkout')
            ->willReturn('checkout_url');

        $workflowItem->expects(self::once())
            ->method('setRedirectUrl')
            ->with('checkout_url');

        $this->purchase->execute($workflowItem);
    }

    public function testExecuteFailed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData([
            'order' => $order,
            'checkout' => $checkout,
            'additional_data' => 'additionalData',
            'email' => 'test@example.com',
        ]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'additionalData' => 'additionalData',
                    'email' => 'test@example.com'
                ]
            )
            ->willReturn(['responseData' => ['purchaseSuccessful' => false]]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => ['purchaseSuccessful' => false],
                        'email' => 'test@example.com'
                    ]
                ]
            );

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'payment_error')
            ->willReturn('payment_error_url');

        $workflowItem->expects(self::once())
            ->method('setRedirectUrl')
            ->with('payment_error_url');

        $this->purchase->execute($workflowItem);
    }

    private function prepareWorkflowItem(
        WorkflowItem|MockObject $workflowItem,
        Checkout $checkout,
        WorkflowData $workflowData,
        WorkflowResult $workflowResult,
        ?int $workflowItemId = 1
    ): void {
        $workflowItem->expects(self::any())
            ->method('getId')
            ->willReturn($workflowItemId);
        $workflowItem->expects(self::any())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowItem->expects(self::any())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowItem->expects(self::any())
            ->method('getResult')
            ->willReturn($workflowResult);
    }
}
