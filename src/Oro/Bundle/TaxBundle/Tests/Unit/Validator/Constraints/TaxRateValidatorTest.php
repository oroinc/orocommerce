<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRate;
use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRateValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TaxRateValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new TaxRateValidator();
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($value, bool $expectedIsValid)
    {
        $constraint = new TaxRate();
        $this->validator->validate($value, $constraint);

        if ($expectedIsValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->taxRateToManyDecimalPlaces)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            [
                'value' => 25,
                'expectedIsValid' => true,
            ],
            [
                'value' => 25.12,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.0,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.1,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.10,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.123456,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.1234567,
                'expectedIsValid' => false,
            ],
            [
                'value' => 0.0000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 0.000000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 11.0000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 11.00000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 1e-200,
                'expectedIsValid' => false,
            ],
            [
                'value' => 'ab',
                'expectedIsValid' => true,
            ],
            [
                'value' => 9.698 / 100,
                'expectedIsValid' => true,
            ],
        ];
    }
}
