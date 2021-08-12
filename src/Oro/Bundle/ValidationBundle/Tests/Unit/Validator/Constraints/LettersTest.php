<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Letters;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LettersTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new RegexValidator();
    }

    protected function createContext()
    {
        $this->constraint = new Letters();

        return parent::createContext();
    }

    public function testConfiguration(): void
    {
        self::assertEquals(RegexValidator::class, $this->constraint->validatedBy());
        self::assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias(): void
    {
        self::assertEquals(Letters::ALIAS, $this->constraint->getAlias());
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
            'correct' => [
                'data' => 'AbcAbc',
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
            ->setCode(Letters::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'not correct' => [
                'data' => 'Abc Abc',
            ]
        ];
    }
}
