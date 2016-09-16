<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOfValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NotBlankOneOfValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var NotBlankOneOfValidator
     */
    protected $validator;

    /**
     * @var NotBlankOneOf
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->getMock(ExecutionContextInterface::class);
        $this->validator = new NotBlankOneOfValidator();
        $this->constraint = new NotBlankOneOf();

        $this->validator->initialize($this->context);
    }

    public function testValidateValid()
    {
        $value = new \stdClass();

        $value->field1 = 'string_value';
        $value->field2 = null;

        $value->field3 = null;
        $value->field4 = 0;

        $this->constraint->fields = [
            [
                'field1' => 'Field 1',
                'field2' => 'Field 2',
            ],
            [
                'field3' => 'Field 3',
                'field4' => 'Field 4'
            ],
        ];

        $this->validator->validate($value, $this->constraint);
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
        $this->constraint->fields = [$fieldGroup];

        $violationBuilder = $this->getMock(ConstraintViolationBuilderInterface::class);

        $violationBuilder->expects($this->at(0))
            ->method('atPath')
            ->with('field1')
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->at(2))
            ->method('atPath')
            ->with('field2')
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));

        $this->context
            ->expects($this->exactly(2))
            ->method('buildViolation')
            ->with($this->constraint->message, [
                '%fields%' => 'Field 1, Field 2'
            ])
            ->willReturn($violationBuilder);

        $this->validator->validate($value, $this->constraint);
    }
}
