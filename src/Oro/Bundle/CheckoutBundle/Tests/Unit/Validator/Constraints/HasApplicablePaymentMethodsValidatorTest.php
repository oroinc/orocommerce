<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\HasApplicablePaymentMethods;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\HasApplicablePaymentMethodsValidator;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HasApplicablePaymentMethodsValidatorTest extends ConstraintValidatorTestCase
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
    protected function createValidator(): HasApplicablePaymentMethodsValidator
    {
        return new HasApplicablePaymentMethodsValidator($this->paymentContextProvider, $this->actionExecutor);
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new HasApplicablePaymentMethods());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('invalid_type', new HasApplicablePaymentMethods());
    }

    public function testValidateWithoutPaymentContext(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);

        $constraint = new HasApplicablePaymentMethods();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(HasApplicablePaymentMethods::CODE)
            ->assertRaised();
    }

    public function testValidateWithNoApplicablePaymentMethods(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with('has_applicable_payment_methods', ['context' => $paymentContext])
            ->willReturn(false);

        $constraint = new HasApplicablePaymentMethods();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(HasApplicablePaymentMethods::CODE)
            ->assertRaised();
    }

    public function testValidateWithApplicablePaymentMethods(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $this->actionExecutor->expects(self::once())
            ->method('evaluateExpression')
            ->with('has_applicable_payment_methods', ['context' => $paymentContext])
            ->willReturn(true);

        $this->validator->validate($checkout, new HasApplicablePaymentMethods());

        $this->assertNoViolation();
    }
}
