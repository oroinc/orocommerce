<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToOrderReview;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToOrderReviewTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private CheckoutPaymentContextProvider|MockObject $paymentContextProvider;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private PaymentMethodActionsInterface|MockObject $paymentMethodActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;

    private ContinueToOrderReview $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->paymentMethodActions = $this->createMock(PaymentMethodActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->transition = new ContinueToOrderReview(
            $this->actionExecutor,
            $this->shippingMethodActions,
            $this->paymentContextProvider,
            $this->checkoutActions,
            $this->paymentMethodActions,
            $this->baseContinueTransition
        );
    }

    /**
     * @dataProvider preConditionDataProvider
     */
    public function testIsPreConditionAllowed(
        bool $isBaseAllowed,
        bool $hasApplicableShippingRules,
        ?PaymentContextInterface $paymentContext,
        bool $hasApplicablePaymentMethods,
        bool $expected
    ): void {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();
        $errors = new ArrayCollection();

        $workflowItem->setEntity($checkout);
        $workflowData = new WorkflowData([
            'line_items_shipping_methods' => ['some_methods'],
            'line_item_groups_shipping_methods' => ['group1' => ['some_group_method']]
        ]);
        $workflowItem->setData($workflowData);

        $this->shippingMethodActions->expects($this->once())
            ->method('actualizeShippingMethods')
            ->with(
                $checkout,
                ['some_methods'],
                ['group1' => ['some_group_method']]
            );

        $this->baseContinueTransition->expects($this->any())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn($isBaseAllowed);

        $this->shippingMethodActions->expects($this->any())
            ->method('hasApplicableShippingRules')
            ->with($checkout, $errors)
            ->willReturn($hasApplicableShippingRules);

        $this->paymentContextProvider->expects($this->any())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                'has_applicable_payment_methods',
                [$paymentContext],
                $errors,
                'oro.checkout.workflow.condition.payment_method_is_not_applicable.message'
            )
            ->willReturn($hasApplicablePaymentMethods);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertSame($expected, $result);
    }

    public function preConditionDataProvider(): array
    {
        return [
            'all conditions met' => [
                'isBaseAllowed' => true,
                'hasApplicableShippingRules' => true,
                'paymentContext' => $this->createMock(PaymentContextInterface::class),
                'hasApplicablePaymentMethods' => true,
                'expected' => true
            ],
            'base not allowed' => [
                'isBaseAllowed' => false,
                'hasApplicableShippingRules' => true,
                'paymentContext' => $this->createMock(PaymentContextInterface::class),
                'hasApplicablePaymentMethods' => true,
                'expected' => false
            ],
            'no applicable shipping rules' => [
                'isBaseAllowed' => true,
                'hasApplicableShippingRules' => false,
                'paymentContext' => $this->createMock(PaymentContextInterface::class),
                'hasApplicablePaymentMethods' => true,
                'expected' => false
            ],
            'no payment context' => [
                'isBaseAllowed' => true,
                'hasApplicableShippingRules' => true,
                'paymentContext' => null,
                'hasApplicablePaymentMethods' => true,
                'expected' => false
            ],
            'no applicable payment methods' => [
                'isBaseAllowed' => true,
                'hasApplicableShippingRules' => true,
                'paymentContext' => $this->createMock(PaymentContextInterface::class),
                'hasApplicablePaymentMethods' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testIsConditionAllowed(
        bool $isValidRequest,
        ?string $paymentMethod,
        bool $isPaymentMethodApplicable,
        bool $expected
    ): void {
        $errors = new ArrayCollection();
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();

        $checkout->setPaymentMethod($paymentMethod);
        $workflowItem->setEntity($checkout);

        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContextProvider->expects($this->any())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap([
                [
                    'check_request',
                    [
                        'is_ajax' => true,
                        'expected_key' => '_wid',
                        'expected_value' => 'ajax_checkout'
                    ],
                    null,
                    null,
                    $isValidRequest
                ],
                [
                    'payment_method_applicable',
                    [
                        'context' => $paymentContext,
                        'payment_method' => $paymentMethod
                    ],
                    $errors,
                    'oro.checkout.workflow.condition.payment_method_was_not_selected.message',
                    $isPaymentMethodApplicable
                ]
            ]);

        $result = $this->transition->isConditionAllowed($workflowItem, $errors);

        $this->assertSame($expected, $result);
    }

    public static function conditionDataProvider(): array
    {
        return [
            [true, 'payment_term', true, true],
            [false, 'payment_term', true, false],
            [true, null, true, false],
            [true, 'payment_term', false, false]
        ];
    }

    public function testExecuteWithoutPaymentValidation(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn(new WorkflowData(['payment_validate' => false]));

        $this->paymentMethodActions->expects($this->never())
            ->method('validate');

        $this->transition->execute($workflowItem);
    }

    public function testExecuteWithPaymentValidation(): void
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $data = new WorkflowData([
            'payment_validate' => true,
            'additional_data' => 'some_data',
            'payment_save_for_later' => true
        ]);

        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->checkoutActions->expects($this->exactly(2))
            ->method('getCheckoutUrl')
            ->willReturnMap([
                [$checkout, null, 'success_url'],
                [$checkout, 'payment_error', 'failure_url'],
            ]);

        $this->paymentMethodActions->expects($this->once())
            ->method('validate')
            ->with(
                $checkout,
                'success_url',
                'failure_url',
                'some_data',
                true
            )
            ->willReturn(['success' => true]);

        $this->transition->execute($workflowItem);

        $this->assertSame(['responseData' => ['success' => true]], $workflowItem->getResult()->toArray());
    }
}
