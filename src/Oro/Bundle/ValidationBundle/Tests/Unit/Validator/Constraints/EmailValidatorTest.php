<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EmailValidator
    {
        return new EmailValidator();
    }

    public function testGetAlias()
    {
        $constraint = new Email();
        $this->assertEquals('email', $constraint->getAlias());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $value, bool $correct)
    {
        $constraint = new Email();
        $this->validator->validate($value, $constraint);

        if ($correct) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ value }}' => '"' . $value . '"'])
                ->setCode(Email::INVALID_FORMAT_ERROR)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'correct' => [
                'value' => 'test_123@test.com',
                'correct' => true
            ],
            'not correct' => [
                'value' => 'test.com',
                'correct' => false
            ]
        ];
    }
}
