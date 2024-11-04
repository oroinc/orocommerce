<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UrlSafeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UrlSafeValidator
    {
        return new UrlSafeValidator();
    }

    public function testGetAlias(): void
    {
        $constraint = new UrlSafe();
        self::assertEquals(UrlSafe::ALIAS, $constraint->getAlias());
    }

    public function testGetDefaultOption(): void
    {
        $constraint = new UrlSafe();
        self::assertNull($constraint->getDefaultOption());
    }

    public function testGetRequiredOptions(): void
    {
        $constraint = new UrlSafe();
        self::assertSame([], $constraint->getRequiredOptions());
    }

    /**
     * @dataProvider validateWrongValueDataProvider
     */
    public function testValidateWrongValue(bool $allowSlashes, string $value): void
    {
        $constraint = new UrlSafe(['allowSlashes' => $allowSlashes]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setParameter('{{ pattern }}', $constraint->pattern)
            ->setCode(UrlSafe::REGEX_FAILED_ERROR)
            ->assertRaised();
    }

    public function validateWrongValueDataProvider(): array
    {
        return [
            'Url not safe' => [
                'allowSlashes' => false,
                'value' => 'Abc/test',
            ],
            'Url not safe with slash on start' => [
                'allowSlashes' => true,
                'value' => '/Abc/test',
            ],
            'Url not safe with slash on end' => [
                'allowSlashes' => true,
                'value' => 'Abc/test/',
            ],
        ];
    }

    /**
     * @dataProvider validateReservedValueDataProvider
     */
    public function testValidateReservedValue(string $value): void
    {
        $constraint = new UrlSafe(['allowSlashes' => true]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->delimiterMessage)
            ->assertRaised();
    }

    public function validateReservedValueDataProvider(): array
    {
        return [
            '_item' => [
                'value' => '_item',
            ],
            '_item/abc' => [
                'value' => '_item/abc',
            ],
            'abc/_item' => [
                'value' => 'abc/_item',
            ],
            'abc/_item/def' => [
                'value' => 'abc/_item/def',
            ],
        ];
    }

    /**
     * @dataProvider validateCorrectValueDataProvider
     */
    public function testValidateCorrectValue(bool $allowSlashes, string $value): void
    {
        $constraint = new UrlSafe(['allowSlashes' => $allowSlashes]);
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validateCorrectValueDataProvider(): array
    {
        return [
            'Url safe' => [
                'allowSlashes' => false,
                'value' => 'ABC-abs_123~45.test',
            ],
            'Url safe with slash' => [
                'allowSlashes' => true,
                'value' => 'ABC-abs_123~45.test/ABC-abs_123~45.test',
            ],
        ];
    }
}
