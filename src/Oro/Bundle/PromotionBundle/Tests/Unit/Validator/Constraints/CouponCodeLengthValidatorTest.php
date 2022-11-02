<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Validator\Constraints\CouponCodeLength;
use Oro\Bundle\PromotionBundle\Validator\Constraints\CouponCodeLengthValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class CouponCodeLengthValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new CouponCodeLengthValidator();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(CodeGenerationOptions $entity, bool $violationExpected)
    {
        $constraint = new CouponCodeLength();
        $this->validator->validate($entity, $constraint);

        if ($violationExpected) {
            $this->buildViolation($constraint->message)
                ->setParameters(['{{ actualLength }}' => 256, '{{ maxAllowedLength }}' => 255])
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            'max length exceeded' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(122)
                    ->setDashesSequence(1)
                    ->setCodePrefix('prefix')
                    ->setCodeSuffix('suffix'),
                'violationExpected' => true,
            ],
            'max length not exceeded' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(121)
                    ->setDashesSequence(1)
                    ->setCodePrefix('prefix')
                    ->setCodeSuffix('suffix'),
                'violationExpected' => false,
            ],
            'max length exceeded without dashes' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(244)
                    ->setCodePrefix('prefix')
                    ->setCodeSuffix('suffix'),
                'violationExpected' => true,
            ],
            'max length not exceeded without dashes' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(243)
                    ->setCodePrefix('prefix')
                    ->setCodeSuffix('suffix'),
                'violationExpected' => false,
            ],
            'max length exceeded only with code length' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(256),
                'violationExpected' => true,
            ],
            'max length not exceeded only with code length' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(255),
                'violationExpected' => false,
            ],
            'max length exceeded with unicode prefix and suffix' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(1)
                    ->setCodePrefix($this->generateUnicodeString(155))
                    ->setCodeSuffix($this->generateUnicodeString(100)),
                'violationExpected' => true,
            ],
            'max length not exceeded with unicode prefix and suffix' => [
                'entity' => (new CodeGenerationOptions())
                    ->setCodeLength(1)
                    ->setCodePrefix($this->generateUnicodeString(154))
                    ->setCodeSuffix($this->generateUnicodeString(100)),
                'violationExpected' => false,
            ],
        ];
    }

    public function testValidateWrongEntity()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new CouponCodeLength();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    private function generateUnicodeString(int $length): string
    {
        // cyrillic symbol
        return str_repeat('Я', $length);
    }
}
