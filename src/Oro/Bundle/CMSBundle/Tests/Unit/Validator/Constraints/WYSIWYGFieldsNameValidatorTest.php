<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGFieldsName;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGFieldsNameValidator;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class WYSIWYGFieldsNameValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new WYSIWYGFieldsNameValidator();
    }

    public function testValidatorArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, NULL given'
        );

        $constraint = new WYSIWYGFieldsName();
        $this->validator->validate(null, $constraint);
    }

    /**
     * @dataProvider fieldDataProvider
     */
    public function testValidateWysiwygField(string $fieldName, string $fieldType): void
    {
        $entityConfigModel = new EntityConfigModel(TestActivity::class);
        $entityConfigModel->setFields(new ArrayCollection([
            new FieldConfigModel('wysiwyg_field', WYSIWYGType::TYPE),
            new FieldConfigModel('wysiwyg_field_style', 'wysiwyg_style'),
            new FieldConfigModel('wysiwyg_field_properties', 'wysiwyg_properties'),
            new FieldConfigModel('string_style', 'string'),
            new FieldConfigModel('string_properties', 'string')
        ]));

        $field = new FieldConfigModel($fieldName, $fieldType);
        $field->setEntity($entityConfigModel);

        $constraint = new WYSIWYGFieldsName();
        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.fieldName')
            ->assertRaised();
    }

    public function fieldDataProvider(): array
    {
        return [
            'WYSIWYG type and fields exist' => [
                'fieldName' => 'wysiwyg_field',
                'type' => WYSIWYGType::TYPE
            ],
            'WYSIWYG type and additional field exist' => [
                'fieldName' => 'string',
                'type' => WYSIWYGType::TYPE
            ],
            'WYSIWYG type and field name equal of one of additional fields(_style)' => [
                'fieldName' => 'wysiwyg_field_style',
                'type' => WYSIWYGType::TYPE
            ],
            'WYSIWYG type and field name equal of one of additional fields(_properties)' => [
                'fieldName' => 'wysiwyg_field_properties',
                'type' => WYSIWYGType::TYPE
            ],
            'String type and additional _style field' => [
                'fieldName' => 'wysiwyg_field_style',
                'type' => Types::STRING
            ],
            'String type and additional _properties field' => [
                'fieldName' => 'wysiwyg_field_properties',
                'type' => Types::STRING
            ]
        ];
    }
}
