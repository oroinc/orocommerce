<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Validator\Constraints\NotBlankDiscountValue;
use Oro\Bundle\OrderBundle\Validator\Constraints\NotBlankDiscountValueValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotBlankDiscountValueValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotBlankDiscountValueValidator
    {
        return new NotBlankDiscountValueValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new OrderDiscount(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new NotBlankDiscountValue());
    }

    public function testUndefinedDiscountType(): void
    {
        $this->validator->validate(new OrderDiscount(), new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testAmountDiscountTypeAndAmountValueSet(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_AMOUNT);
        $value->setAmount(1);
        $this->validator->validate($value, new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testPercentDiscountTypeAndPercentValueSet(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_PERCENT);
        $value->setPercent(0.5);
        $this->validator->validate($value, new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testAmountDiscountTypeAndAmountValueIsZero(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_AMOUNT);
        $value->setAmount(0);
        $this->validator->validate($value, new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testPercentDiscountTypeAndPercentValueIsZero(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_PERCENT);
        $value->setPercent(0);
        $this->validator->validate($value, new NotBlankDiscountValue());
        $this->assertNoViolation();
    }

    public function testAmountDiscountTypeAndAmountValueNotSet(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_AMOUNT);
        $constraint = new NotBlankDiscountValue();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('property.path.amount')
            ->assertRaised();
    }

    public function testPercentDiscountTypeAndPercentValueNotSet(): void
    {
        $value = new OrderDiscount();
        $value->setType(OrderDiscount::TYPE_PERCENT);
        $constraint = new NotBlankDiscountValue();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('property.path.percent')
            ->assertRaised();
    }
}
