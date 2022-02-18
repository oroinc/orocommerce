<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Alphanumeric;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AlphanumericValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RegexValidator
    {
        return new RegexValidator();
    }

    public function testGetAlias(): void
    {
        $constraint = new Alphanumeric();
        self::assertEquals(Alphanumeric::ALIAS, $constraint->getAlias());
    }

    public function testGetDefaultOption(): void
    {
        $constraint = new Alphanumeric();
        self::assertNull($constraint->getDefaultOption());
    }

    public function testGetRequiredOptions(): void
    {
        $constraint = new Alphanumeric();
        self::assertSame([], $constraint->getRequiredOptions());
    }

    /**
     * @dataProvider validateCorrectValueDataProvider
     */
    public function testValidateCorrectValue(string|int $value): void
    {
        $constraint = new Alphanumeric();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validateCorrectValueDataProvider(): array
    {
        return [
            'alphanumeric' => ['10ten'],
            'int number' => [10],
            'alphabet' => ['abcdefg'],
        ];
    }

    /**
     * @dataProvider validateWrongValueDataProvider
     */
    public function testValidateWrongValue(string|float $value): void
    {
        $constraint = new Alphanumeric();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setCode(Alphanumeric::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'decimal' => [3.14],
            'symbols' => ['!@#test'],
            'alphanumeric with space' => ['10 ten']
        ];
    }
}
