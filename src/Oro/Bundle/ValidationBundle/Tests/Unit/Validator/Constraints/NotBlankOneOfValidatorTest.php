<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOfValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotBlankOneOfValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotBlankOneOfValidator
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        return new NotBlankOneOfValidator($translator);
    }

    public function testValidateValid()
    {
        $value = new \stdClass();

        $value->field1 = 'string_value';
        $value->field2 = null;

        $value->field3 = null;
        $value->field4 = 0;

        $constraint = new NotBlankOneOf();
        $constraint->fields = [
            [
                'field1' => 'Field 1',
                'field2' => 'Field 2',
            ],
            [
                'field3' => 'Field 3',
                'field4' => 'Field 4'
            ],
        ];

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNotValid()
    {
        $value = new \stdClass();
        $value->field1 = null;
        $value->field2 = null;
        $fieldGroup = [
            'field1' => 'Field 1',
            'field2' => 'Field 2',
        ];

        $constraint = new NotBlankOneOf();
        $constraint->fields = [$fieldGroup];

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('%fields%', 'Field 1 (translated), Field 2 (translated)')
            ->atPath('property.path.field1')
            ->buildNextViolation($constraint->message)
            ->setParameter('%fields%', 'Field 1 (translated), Field 2 (translated)')
            ->atPath('property.path.field2')
            ->assertRaised();
    }
}
