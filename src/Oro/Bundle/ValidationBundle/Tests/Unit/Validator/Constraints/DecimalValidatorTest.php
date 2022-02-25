<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\DecimalValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DecimalValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DecimalValidator
    {
        return new DecimalValidator();
    }

    public function testGetAlias()
    {
        $constraint = new Decimal();
        $this->assertEquals('decimal', $constraint->getAlias());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string|float|int|null $value, bool $correct, string $locale = 'en')
    {
        \Locale::setDefault($locale);

        $constraint = new Decimal();
        $this->validator->validate($value, $constraint);

        if ($correct) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ value }}' => '"' . $value . '"'])
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'int' => [
                'value' => 10,
                'correct' => true
            ],
            'float' => [
                'value' => 10.45650,
                'correct' => true
            ],
            'string float' => [
                'value' => '10.4565',
                'correct' => true
            ],
            'string float with trailing zeros fr' => [
                'value' => '10.4560000000000000000000',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float with trailing zeros without fraction part fr' => [
                'value' => '10.0000000000000000000',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float 100 fr' => [
                'value' => '100',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float fr' => [
                'value' => '10.456500000000000000000001',
                'correct' => false,
                'locale' => 'fr'
            ],
            'string float with grouping' => [
                'value' => '12,210.4565',
                'correct' => true
            ],
            'null' => [
                'value' => null,
                'correct' => true
            ],
            'string with float' => [
                'value' => '10.45650 string',
                'correct' => false
            ],
        ];
    }

    public function testNotScalar()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new Decimal());
    }
}
