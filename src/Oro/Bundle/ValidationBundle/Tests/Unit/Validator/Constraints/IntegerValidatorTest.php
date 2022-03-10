<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Oro\Bundle\ValidationBundle\Validator\Constraints\IntegerValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IntegerValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): IntegerValidator
    {
        return new IntegerValidator();
    }

    public function testGetAlias()
    {
        $constraint = new Integer();
        $this->assertEquals('integer', $constraint->getAlias());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string|float|int|null $value, bool $correct)
    {
        $constraint = new Integer();
        $this->validator->validate($value, $constraint);

        if ($correct) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ value }}' => is_string($value) ? '"' . $value . '"' : $value])
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'int number' => [
                'value' => 10,
                'correct' => true
            ],
            'string with int' => [
                'value' => '10',
                'correct' => true
            ],
            'int with separator' => [
                'value' => '10,000',
                'correct' => true
            ],
            'int with precision ' => [
                'value' => 10.00,
                'correct' => true
            ],
            'float number' => [
                'value' => 10.50,
                'correct' => false
            ],
            'string' => [
                'value' => 'ten',
                'correct' =>false
            ],
            'null' => [
                'value' => null,
                'correct' => true
            ],
        ];
    }

    public function testNotScalar()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new Integer());
    }
}
