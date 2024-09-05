<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\PaymentMethodActions;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodActionsTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private PaymentMethodActions $paymentMethodActions;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);


        $this->paymentMethodActions = new PaymentMethodActions(
            $this->actionExecutor
        );
    }

    public function testValidateReturnsEmptyArrayWhenPaymentMethodNotSupported(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn(null);
        $this->actionExecutor->expects($this->never())
            ->method('evaluateExpression');

        $result = $this->paymentMethodActions->validate(
            $checkout,
            'success_url',
            'failure_url',
            'additional_data',
            true
        );

        $this->assertSame([], $result);
    }

    public function testValidateReturnsArrayWhenPaymentMethodIsSupported(): void
    {
        $paymentMethod = 'sample_payment_method';

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $checkout->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('payment_method_supports', [
                'payment_method' => $paymentMethod,
                'action' => PaymentMethodInterface::VALIDATE,
            ])
            ->willReturn(true);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('payment_validate', [
                'attribute' => null,
                'object' => $checkout,
                'paymentMethod' => $paymentMethod,
                'transactionOptions' => [
                    'saveForLaterUse' => true,
                    'successUrl' => 'success_url',
                    'failureUrl' => 'failure_url',
                    'additionalData' => 'additional_data',
                    'checkoutId' => 1
                ]
            ])
            ->willReturn(['attribute' => ['some_result_data']]);

        $result = $this->paymentMethodActions->validate(
            $checkout,
            'success_url',
            'failure_url',
            'additional_data',
            true
        );

        $this->assertSame(['some_result_data'], $result);
    }
}
