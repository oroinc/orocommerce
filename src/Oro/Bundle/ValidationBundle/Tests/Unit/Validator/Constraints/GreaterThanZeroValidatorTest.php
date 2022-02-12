<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Validator\Constraints\GreaterThanValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class GreaterThanZeroValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): GreaterThanValidator
    {
        return new GreaterThanValidator();
    }

    public function testGetAlias()
    {
        $constraint = new GreaterThanZero();
        $this->assertEquals('greater_than_zero', $constraint->getAlias());
    }

    public function testGetDefaultOption()
    {
        $constraint = new GreaterThanZero();
        $this->assertEquals(null, $constraint->getDefaultOption());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(int $value, bool $correct)
    {
        $constraint = new GreaterThanZero();
        $this->validator->validate($value, $constraint);

        if ($correct) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters([
                    '{{ value }}' => $value,
                    '{{ compared_value }}' => '0',
                    '{{ compared_value_type }}' => 'int'
                ])
                ->setCode(GreaterThanZero::TOO_LOW_ERROR)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'correct' => [
                'value' => 20,
                'correct' => true
            ],
            'zero' => [
                'value' => 0,
                'correct' => false
            ],
            'not correct' => [
                'value' => -20,
                'correct' => false
            ]
        ];
    }
}
