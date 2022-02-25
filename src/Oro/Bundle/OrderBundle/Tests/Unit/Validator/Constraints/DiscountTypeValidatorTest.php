<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountType;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DiscountTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DiscountTypeValidator
    {
        return new DiscountTypeValidator();
    }

    public function testGetTargets()
    {
        $constraint = new DiscountType();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateForUnsupportedValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be instance of "Oro\Bundle\OrderBundle\Entity\OrderDiscount"');

        $constraint = new DiscountType();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    /**
     * @dataProvider validValueDataProvider
     */
    public function testValidateForValidValue(OrderDiscount $value)
    {
        $constraint = new DiscountType();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validValueDataProvider(): array
    {
        return [
            [(new OrderDiscount())->setType(OrderDiscount::TYPE_AMOUNT)],
            [(new OrderDiscount())->setType(OrderDiscount::TYPE_PERCENT)]
        ];
    }

    /**
     * @dataProvider invalidValueDataProvider
     */
    public function testValidateForInvalidValue(OrderDiscount $value)
    {
        $constraint = new DiscountType();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->errorMessage)
            ->setParameters([
                '%valid_types%' => implode(',', [OrderDiscount::TYPE_AMOUNT, OrderDiscount::TYPE_PERCENT])
            ])
            ->atPath('property.path.type')
            ->assertRaised();
    }

    public function invalidValueDataProvider(): array
    {
        return [
            [new OrderDiscount()],
            [(new OrderDiscount())->setType('someType')]
        ];
    }
}
