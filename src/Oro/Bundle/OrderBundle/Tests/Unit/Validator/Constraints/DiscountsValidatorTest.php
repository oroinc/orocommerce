<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Validator\Constraints\Discounts;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DiscountsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DiscountsValidator
    {
        return new DiscountsValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new Order(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new Discounts());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new Discounts());
    }

    public function testNoTotalDiscounts(): void
    {
        $value = new Order();
        $value->setSubtotal(100);
        $this->validator->validate($value, new Discounts());
        $this->assertNoViolation();
    }

    public function testNoSubtotal(): void
    {
        $value = new Order();
        $value->setTotalDiscounts(Price::create(101, 'USD'));
        $this->validator->validate($value, new Discounts());
        $this->assertNoViolation();
    }

    public function testSubtotalIsEqualToTotalDiscounts(): void
    {
        $value = new Order();
        $value->setSubtotal(101);
        $value->setTotalDiscounts(Price::create(101, 'USD'));
        $this->validator->validate($value, new Discounts());
        $this->assertNoViolation();
    }

    public function testSubtotalIsGreaterThanTotalDiscounts(): void
    {
        $value = new Order();
        $value->setSubtotal(102);
        $value->setTotalDiscounts(Price::create(101, 'USD'));
        $this->validator->validate($value, new Discounts());
        $this->assertNoViolation();
    }

    public function testValidateFailsWhenNoSuchErrorsPreviously(): void
    {
        $value = new Order();
        $value->setSubtotal(100);
        $value->setTotalDiscounts(Price::create(101, 'USD'));

        $constraint = new Discounts();
        $this->setValue($value);
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->errorMessage)
            ->setInvalidValue($value)
            ->atPath('property.path.totalDiscountsAmount')
            ->assertRaised();
    }

    public function testValidateFailsWhenWasErrorPreviously(): void
    {
        $value = new Order();
        $value->setSubtotal(100);
        $value->setTotalDiscounts(Price::create(101, 'USD'));

        $constraint = new Discounts();

        $context = $this->createMock(ExecutionContext::class);
        $context->expects(self::any())
            ->method('getValue')
            ->willReturn($value);
        $context->expects(self::once())
            ->method('getViolations')
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation('msg', null, [], null, null, $value, null, null, $constraint)
            ]));
        $context->expects(self::never())
            ->method('buildViolation');

        $this->validator->initialize($context);
        $this->validator->validate($value, $constraint);
    }
}
