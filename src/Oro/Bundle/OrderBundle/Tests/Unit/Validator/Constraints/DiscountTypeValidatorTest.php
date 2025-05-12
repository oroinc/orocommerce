<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountType;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DiscountTypeValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): DiscountTypeValidator
    {
        return new DiscountTypeValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(OrderDiscount::TYPE_AMOUNT, $this->createMock(Constraint::class));
    }

    /**
     * @dataProvider validValueDataProvider
     */
    public function testValidateForValidValue(mixed $value): void
    {
        $constraint = new DiscountType();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validValueDataProvider(): array
    {
        return [
            [123],
            [OrderDiscount::TYPE_AMOUNT],
            [OrderDiscount::TYPE_PERCENT]
        ];
    }

    /**
     * @dataProvider invalidValueDataProvider
     */
    public function testValidateForInvalidValue(mixed $value): void
    {
        $constraint = new DiscountType();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters([
                '%valid_types%' => implode(', ', [OrderDiscount::TYPE_AMOUNT, OrderDiscount::TYPE_PERCENT])
            ])
            ->assertRaised();
    }

    public function invalidValueDataProvider(): array
    {
        return [
            [null],
            ['someType']
        ];
    }
}
