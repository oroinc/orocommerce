<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\ApplicablePaymentMethod;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\ApplicablePaymentMethodValidator;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\HasApplicablePaymentMethods;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ApplicablePaymentMethodValidatorTest extends ConstraintValidatorTestCase
{
    private CheckoutPaymentContextProvider&MockObject $paymentContextProvider;
    private ApplicablePaymentMethodsProvider&MockObject $paymentMethodsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->paymentMethodsProvider = $this->createMock(ApplicablePaymentMethodsProvider::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ApplicablePaymentMethodValidator
    {
        return new ApplicablePaymentMethodValidator($this->paymentContextProvider, $this->paymentMethodsProvider);
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new ApplicablePaymentMethod());

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
        $checkout->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method_1');

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn(null);

        $constraint = new ApplicablePaymentMethod();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(ApplicablePaymentMethod::CODE)
            ->assertRaised();
    }

    public function testValidateWithNoApplicablePaymentMethods(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method_1');

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $constraint = new ApplicablePaymentMethod();
        $this->validator->validate($checkout, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithDisabledPaymentMethod(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_invalid');

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::exactly(2))
            ->method('getPaymentMethod')
            ->willReturn('payment_method_1');

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodsProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($paymentContext)
            ->willReturn([$paymentMethod]);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $constraint = new ApplicablePaymentMethod();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(ApplicablePaymentMethod::CODE)
            ->assertRaised();
    }

    public function testValidateWithValidPaymentMethod(): void
    {
        $paymentMethod1 = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod1->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_invalid');

        $paymentMethod2 = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod2->expects(self::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_1');

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::exactly(2))
            ->method('getPaymentMethod')
            ->willReturn('payment_method_1');

        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->paymentMethodsProvider->expects(self::once())
            ->method('getApplicablePaymentMethods')
            ->with($paymentContext)
            ->willReturn([$paymentMethod1, $paymentMethod2]);

        $this->paymentContextProvider->expects(self::once())
            ->method('getContext')
            ->with(self::identicalTo($checkout))
            ->willReturn($paymentContext);

        $constraint = new ApplicablePaymentMethod();
        $this->validator->validate($checkout, $constraint);

        $this->assertNoViolation();
    }
}
