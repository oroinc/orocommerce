<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UrlSafeTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new RegexValidator();
    }

    protected function createContext()
    {
        $this->constraint = new UrlSafe();

        return parent::createContext();
    }

    public function testConfiguration(): void
    {
        self::assertEquals(RegexValidator::class, $this->constraint->validatedBy());
        self::assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias(): void
    {
        self::assertEquals(UrlSafe::ALIAS, $this->constraint->getAlias());
    }

    /**
     * @dataProvider validateWrongValueDataProvider
     *
     * @param bool $allowSlashes
     * @param mixed $data
     */
    public function testValidateWrongValue(bool $allowSlashes, $data): void
    {
        $constraint = new UrlSafe(['allowSlashes' => $allowSlashes]);

        $this->validator->validate($data, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"' . $data . '"')
            ->setCode(UrlSafe::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'Url not safe' => [
                'allowSlashes' => false,
                'data' => 'Abc/test',
            ],
            'Url not safe with slash on start' => [
                'allowSlashes' => true,
                'data' => '/Abc/test',
            ],
            'Url not safe with slash on end' => [
                'allowSlashes' => true,
                'data' => 'Abc/test/',
            ],
        ];
    }

    /**
     * @dataProvider validateCorrectValueDataProvider
     *
     * @param bool $allowSlashes
     * @param mixed $data
     */
    public function testValidateCorrectValue(bool $allowSlashes, $data): void
    {
        $constraint = new UrlSafe(['allowSlashes' => $allowSlashes]);

        $this->validator->validate($data, $constraint);

        $this->assertNoViolation();
    }

    public function validateCorrectValueDataProvider(): array
    {
        return [
            'Url safe' => [
                'allowSlashes' => false,
                'data' => 'ABC-abs_123~45.test',
                'correct' => true
            ],
            'Url safe with slash' => [
                'allowSlashes' => true,
                'data' => 'ABC-abs_123~45.test/ABC-abs_123~45.test',
                'correct' => true
            ],
        ];
    }

    public function testGetDefaultOption(): void
    {
        self::assertNull($this->constraint->getDefaultOption());
    }
}
