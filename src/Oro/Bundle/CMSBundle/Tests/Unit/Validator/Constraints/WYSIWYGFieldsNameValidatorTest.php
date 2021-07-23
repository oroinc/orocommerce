<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\TextType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGFieldsName;
use Oro\Bundle\CMSBundle\Validator\Constraints\WYSIWYGFieldsNameValidator;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class WYSIWYGFieldsNameValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testValidatorArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, NULL given'
        );

        $constraint = new WYSIWYGFieldsName();
        $validator = new WYSIWYGFieldsNameValidator();
        $validator->validate(null, $constraint);
    }

    /**
     * @dataProvider fieldDataProvider
     */
    public function testValidateWysiwygField(string $fieldName, string $fieldType, int $countError): void
    {
        $entityConfigModel = new EntityConfigModel(TestActivity::class);
        $entityConfigModel->setFields(new ArrayCollection([
            $this->getEntity(FieldConfigModel::class, ['fieldName' => 'wysiwyg_field', 'type' => WYSIWYGType::TYPE]),
            $this->getEntity(FieldConfigModel::class, [
                'fieldName' => 'wysiwyg_field_style',
                'type' => 'wysiwyg_style'
            ]),
            $this->getEntity(FieldConfigModel::class, [
                'fieldName' => 'wysiwyg_field_properties',
                'type' => 'wysiwyg_properties'
            ]),
            $this->getEntity(FieldConfigModel::class, ['fieldName' => 'string_style', 'type' => 'string']),
            $this->getEntity(FieldConfigModel::class, ['fieldName' => 'string_properties', 'type' => 'string']),
        ]));

        /** @var FieldConfigModel $field */
        $field = $this->getEntity(
            FieldConfigModel::class,
            ['fieldName' => $fieldName, 'type' => $fieldType, 'entity' => $entityConfigModel]
        );

        $constraint = new WYSIWYGFieldsName();
        $validator = new WYSIWYGFieldsNameValidator();
        $validator->initialize($this->createValidationContext($countError));
        $validator->validate($field, $constraint);
    }

    public function fieldDataProvider(): array
    {
        return [
            'WYSIWYG type and fields exist' => [
                'fieldName' => 'wysiwyg_field',
                'type' => WYSIWYGType::TYPE,
                'countError' => 1
            ],
            'WYSIWYG type and additional field exist' => [
                'fieldName' => 'string',
                'type' => WYSIWYGType::TYPE,
                'countError' => 1
            ],
            'WYSIWYG type and field name equal of one of additional fields(_style)' => [
                'fieldName' => 'wysiwyg_field_style',
                'type' => WYSIWYGType::TYPE,
                'countError' => 1
            ],
            'WYSIWYG type and field name equal of one of additional fields(_properties)' => [
                'fieldName' => 'wysiwyg_field_properties',
                'type' => WYSIWYGType::TYPE,
                'countError' => 1
            ],
            'String type and additional _style field' => [
                'fieldName' => 'wysiwyg_field_style',
                'type' => TextType::STRING,
                'countError' => 1
            ],
            'String type and additional _properties field' => [
                'fieldName' => 'wysiwyg_field_properties',
                'type' => TextType::STRING,
                'countError' => 1
            ],
        ];
    }

    private function createValidationContext(int $expect = 0): ExecutionContextInterface
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintViolationBuilder
            ->expects($this->exactly($expect))
            ->method('atPath')
            ->with('fieldName')
            ->willReturnSelf();
        $constraintViolationBuilder
            ->expects($this->exactly($expect))
            ->method('addViolation');

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects($this->exactly($expect))
            ->method('buildViolation')
            ->with('oro.cms.wysiwyg.field_name_exist')
            ->willReturn($constraintViolationBuilder);

        return $context;
    }
}
