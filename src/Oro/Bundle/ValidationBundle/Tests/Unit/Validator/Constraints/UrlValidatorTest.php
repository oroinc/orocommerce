<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UrlValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UrlValidator
    {
        return new UrlValidator();
    }

    public function testGetAlias()
    {
        $constraint = new Url();
        $this->assertEquals('url', $constraint->getAlias());
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $value, bool $correct)
    {
        $constraint = new Url();
        $this->validator->validate($value, $constraint);

        if ($correct) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ value }}' => '"' . $value . '"'])
                ->setCode(Url::INVALID_URL_ERROR)
                ->assertRaised();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'Url safe' => [
                'value' => 'http://www.test.com/test',
                'correct' => true
            ],
            'Url not safe' => [
                'value' => '_Abc/test',
                'correct' => false
            ],
        ];
    }
}
