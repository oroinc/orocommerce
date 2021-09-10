<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Alphanumeric;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AlphanumericTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new RegexValidator();
    }

    protected function createContext()
    {
        $this->constraint = new Alphanumeric();

        return parent::createContext();
    }

    public function testConfiguration(): void
    {
        self::assertEquals(RegexValidator::class, $this->constraint->validatedBy());
        self::assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias(): void
    {
        self::assertEquals(Alphanumeric::ALIAS, $this->constraint->getAlias());
    }

    public function testGetDefaultOption(): void
    {
        self::assertNull($this->constraint->getDefaultOption());
    }

    /**
     * @dataProvider validateCorrectValueDataProvider
     * @param mixed $data
     */
    public function testValidateCorrectValue($data): void
    {
        $this->validator->validate($data, $this->constraint);

        $this->assertNoViolation();
    }

    public function validateCorrectValueDataProvider(): array
    {
        return [
            'alphanumeric' => [
                'data' => '10ten',
            ],
            'int number' => [
                'data' => 10,
            ],
            'alphabet' => [
                'data' => 'abcdefg',
            ],
        ];
    }

    /**
     * @dataProvider validateWrongValueDataProvider
     * @param mixed $data
     */
    public function testValidateWrongValue($data): void
    {
        $this->validator->validate($data, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->setParameter('{{ value }}', '"' . $data . '"')
            ->setCode(Alphanumeric::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'decimal' => [
                'data' => 3.14,
            ],
            'symbols' => [
                'data' => '!@#test',
            ],
            'alphanumeric with space' => [
                'data' => '10 ten',
            ]
        ];
    }
}
