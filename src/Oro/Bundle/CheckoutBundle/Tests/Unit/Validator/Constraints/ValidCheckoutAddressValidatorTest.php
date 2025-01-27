<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Validator\Constraints\ValidCheckoutAddress;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\ValidCheckoutAddressValidator;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidCheckoutAddressValidatorTest extends TestCase
{
    private ExecutionContextInterface|MockObject $context;
    private ValidatorInterface|MockObject $contextValidator;
    private ValidCheckoutAddressValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextValidator = $this->createMock(ValidatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ValidCheckoutAddressValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateWithNullValue(): void
    {
        $this->context->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate(null, new ValidCheckoutAddress());
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new ValidCheckoutAddress());
    }

    public function testValidateWithNoViolations(): void
    {
        $orderAddress = new OrderAddress();

        $this->context->expects(self::once())
            ->method('getValidator')
            ->willReturn($this->contextValidator);

        $this->contextValidator->expects(self::once())
            ->method('validate')
            ->with($orderAddress, null, null)
            ->willReturn(new ConstraintViolationList());

        $this->context->expects(self::never())
            ->method('buildViolation');

        $this->validator->validate($orderAddress, new ValidCheckoutAddress());
    }

    public function testValidateWithViolations(): void
    {
        $orderAddress = new OrderAddress();

        $this->context->expects(self::once())
            ->method('getValidator')
            ->willReturn($this->contextValidator);

        $this->contextValidator->expects(self::once())
            ->method('validate')
            ->with($orderAddress, null, null)
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $constraint = new ValidCheckoutAddress();

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects(self::once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);
        $violationBuilder->expects(self::once())
            ->method('addViolation');

        $this->validator->validate($orderAddress, $constraint);
    }
}
