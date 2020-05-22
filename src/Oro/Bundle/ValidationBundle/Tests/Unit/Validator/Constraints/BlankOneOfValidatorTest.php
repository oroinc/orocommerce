<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOfValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BlankOneOfValidatorTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var BlankOneOfValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->translator->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );

        $this->validator = new BlankOneOfValidator(
            $this->translator,
            $this->propertyAccessor
        );

        $this->validator->initialize($this->context);
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

        $this->context->expects(static::never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
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

        $violationBuilder1 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder2 = $this->createMock(ConstraintViolationBuilderInterface::class);

        $violationBuilder1->expects(static::once())
            ->method('atPath')
            ->with('field1')
            ->willReturn($violationBuilder1);

        $violationBuilder2->expects(static::once())
            ->method('atPath')
            ->with('field3')
            ->willReturn($violationBuilder2);

        $this->context
            ->expects(static::at(0))
            ->method('buildViolation')
            ->willReturn($violationBuilder1);

        $this->context
            ->expects(static::at(1))
            ->method('buildViolation')
            ->willReturn($violationBuilder2);

        $this->validator->validate($value, $constraint);
    }
}
