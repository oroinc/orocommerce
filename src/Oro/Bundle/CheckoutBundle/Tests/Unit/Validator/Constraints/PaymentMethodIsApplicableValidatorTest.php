<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\PaymentMethodIsApplicable;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\PaymentMethodIsApplicableValidator;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PaymentMethodIsApplicableValidatorTest extends ConstraintValidatorTestCase
{
    private CheckoutPaymentContextProvider&MockObject $paymentContextProvider;
    private ActionExecutor&MockObject $actionExecutor;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): PaymentMethodIsApplicableValidator
    {
        return new PaymentMethodIsApplicableValidator($this->paymentContextProvider, $this->actionExecutor);
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new PaymentMethodIsApplicable());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('invalid_type', new PaymentMethodIsApplicable());
    }

    public function testValidateWithoutPaymentMethod(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn(null);

        $this->paymentContextProvider->expects(self::never())
            ->method('getContext');

        $constraint = new PaymentMethodIsApplicable();
        $this->validator->validate($checkout, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutPaymentContext(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method');

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);

        $constraint = new PaymentMethodIsApplicable();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(PaymentMethodIsApplicable::CODE)
            ->assertRaised();
    }

    public function testValidateWithNonApplicablePaymentMethod(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::atLeastOnce())
            ->method('getPaymentMethod')
            ->willReturn('non_applicable_method');

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with('payment_method_applicable', [
                'context' => $paymentContext,
                'payment_method' => 'non_applicable_method'
            ])
            ->willReturn(false);

        $constraint = new PaymentMethodIsApplicable();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(PaymentMethodIsApplicable::CODE)
            ->assertRaised();
    }

    public function testValidateWithApplicablePaymentMethod(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::atLeastOnce())
            ->method('getPaymentMethod')
            ->willReturn('applicable_method');

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with('payment_method_applicable', [
                'context' => $paymentContext,
                'payment_method' => 'applicable_method'
            ])
            ->willReturn(true);

        $this->validator->validate($checkout, new PaymentMethodIsApplicable());

        $this->assertNoViolation();
    }
}
