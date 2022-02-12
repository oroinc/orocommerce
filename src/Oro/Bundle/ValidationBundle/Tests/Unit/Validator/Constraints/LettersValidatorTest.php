<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Letters;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LettersValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): RegexValidator
    {
        return new RegexValidator();
    }

    public function testGetAlias(): void
    {
        $constraint = new Letters();
        self::assertEquals(Letters::ALIAS, $constraint->getAlias());
    }

    public function testGetDefaultOption(): void
    {
        $constraint = new Letters();
        self::assertNull($constraint->getDefaultOption());
    }

    public function testGetRequiredOptions(): void
    {
        $constraint = new Letters();
        self::assertSame([], $constraint->getRequiredOptions());
    }

    /**
     * @dataProvider validateCorrectValueDataProvider
     */
    public function testValidateCorrectValue(string $value): void
    {
        $constraint = new Letters();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validateCorrectValueDataProvider(): array
    {
        return [
            'correct' => ['AbcAbc'],
        ];
    }

    /**
     * @dataProvider validateWrongValueDataProvider
     */
    public function testValidateWrongValue(string $value): void
    {
        $constraint = new Letters();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setCode(Letters::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'not correct' => ['Abc Abc']
        ];
    }
}
