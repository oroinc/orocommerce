<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOfValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlankOneOfValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): BlankOneOfValidator
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        return new BlankOneOfValidator($translator, PropertyAccess::createPropertyAccessor());
    }

    public function testGetTargets()
    {
        $constraint = new BlankOneOf();
        self::assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateCorrectFields()
    {
        $value = new \stdClass();

        $value->field1 = 'test';
        $value->field2 = null;

        $value->field3 = '';
        $value->field4 = 0;

        $value->field5 = '0';
        $value->field6 = '';

        $constraint = new BlankOneOf();
        $constraint->fields = [
            ['field1' => 'Field 1', 'field2' => 'Field 2'],
            ['field3' => 'Field 3', 'field4' => 'Field 4'],
            ['field5' => 'Field 5', 'field6' => 'Field 6'],
        ];

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWrongValues()
    {
        $value = new \stdClass();

        $value->field1 = 'test';
        $value->field2 = 'test2';

        $value->field3 = '0';
        $value->field4 = 0;

        $value->field5 = '';
        $value->field6 = null;

        $constraint = new BlankOneOf();
        $constraint->fields = [
            ['field1' => 'Field 1', 'field2' => 'Field 2'],
            ['field3' => 'Field 3', 'field4' => 'Field 4'],
            ['field5' => 'Field 5', 'field6' => 'Field 6'],
        ];

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('%fields%', 'Field 1 (translated), Field 2 (translated)')
            ->atPath('property.path.field1')
            ->buildNextViolation($constraint->message)
            ->setParameter('%fields%', 'Field 3 (translated), Field 4 (translated)')
            ->atPath('property.path.field3')
            ->assertRaised();
    }
}
