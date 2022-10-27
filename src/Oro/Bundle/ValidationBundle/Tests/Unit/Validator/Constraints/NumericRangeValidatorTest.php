<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRange;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRangeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NumericRangeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NumericRangeValidator
    {
        return new NumericRangeValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NumericRange());
        $this->assertNoViolation();
    }

    public function testNotNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('not a number', $constraint);
        $this->buildViolation($constraint->invalidMessage)
            ->setCode(NumericRange::INVALID_CHARACTERS_ERROR)
            ->assertRaised();
    }

    public function testIntegerValue(): void
    {
        $this->validator->validate(10, new NumericRange());
        $this->assertNoViolation();
    }

    public function testFloatValue(): void
    {
        $this->validator->validate(10.0, new NumericRange());
        $this->assertNoViolation();
    }

    public function testStringNumericValue(): void
    {
        $this->validator->validate('10', new NumericRange());
        $this->assertNoViolation();
    }

    public function testBigNumericValue(): void
    {
        $this->validator->validate('999999999999999.9999', new NumericRange());
        $this->assertNoViolation();
    }

    public function testTooBigNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('1000000000000000', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '999999999999999.9999')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testTooBigFractionPartOfNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('999999999999999.9999000000000000001', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '999999999999999.9999')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testTooBigIntegralAndFractionPartOfNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('159132647246919822550452576.9150983494511948540991657', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '999999999999999.9999')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testNegativeNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('-100.9150', $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ limit }}', '0')
            ->setCode(NumericRange::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testSmallNegativeNumericValue(): void
    {
        $constraint = new NumericRange();
        $this->validator->validate('-0.000000000000000000000000000000000000001', $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ limit }}', '0')
            ->setCode(NumericRange::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testFloatValueGreaterThanCustomMaxLimit(): void
    {
        $constraint = new NumericRange(['max' => 100]);
        $this->validator->validate(100.001, $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '100')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testFloatValueLessThanCustomMinLimit(): void
    {
        $constraint = new NumericRange(['min' => '-100']);
        $this->validator->validate(-100.001, $constraint);
        $this->buildViolation($constraint->minMessage)
            ->setParameter('{{ limit }}', '-100')
            ->setCode(NumericRange::TOO_LOW_ERROR)
            ->assertRaised();
    }

    public function testNumericValueGreaterThanCustomMinLimit(): void
    {
        $this->validator->validate(
            '-99.99999999999999999999999999999999999999999',
            new NumericRange(['min' => '-100'])
        );
        $this->assertNoViolation();
    }

    public function testIntegralPartOfValueGreaterThanPrecision(): void
    {
        $constraint = new NumericRange(['precision' => 3, 'scale' => 0]);
        $this->validator->validate(1000, $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '999')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testIntegralPartOfValueGreaterThanDefaultPrecision(): void
    {
        $constraint = new NumericRange(['scale' => 2]);
        $this->validator->validate('99999999999999999.991', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '99999999999999999.99')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testFractionalPartOfValueGreaterThanScale(): void
    {
        $constraint = new NumericRange(['precision' => 3, 'scale' => 2]);
        $this->validator->validate('9.990001', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '9.99')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testFractionalPartOfValueGreaterThanDefaultScale(): void
    {
        $constraint = new NumericRange(['precision' => 5]);
        $this->validator->validate('9.99991', $constraint);
        $this->buildViolation($constraint->maxMessage)
            ->setParameter('{{ limit }}', '9.9999')
            ->setCode(NumericRange::TOO_HIGH_ERROR)
            ->assertRaised();
    }

    public function testCustomPrecisionAndScale(): void
    {
        $this->validator->validate(0, new NumericRange(['precision' => 3, 'scale' => 2]));
        $this->assertNoViolation();
    }

    public function testCustomPrecision(): void
    {
        $this->validator->validate('9.9998', new NumericRange(['precision' => 5]));
        $this->assertNoViolation();
    }
}
