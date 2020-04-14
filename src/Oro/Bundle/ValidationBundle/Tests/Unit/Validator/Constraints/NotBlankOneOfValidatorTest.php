<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOf;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NotBlankOneOfValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotBlankOneOfValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
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
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translatedLabel = ' key was translated.';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new NotBlankOneOf();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with(
                $this->logicalOr(
                    $this->equalTo('Field 1'),
                    $this->equalTo('Field 2')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($param) {
                        return $param . $this->translatedLabel;
                    }
                )
            );

        $this->validator = new NotBlankOneOfValidator($this->translator);

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

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $violationBuilder->expects($this->at(0))
            ->method('atPath')
            ->with('field1')
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->at(2))
            ->method('atPath')
            ->with('field2')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));

        $this->context
            ->expects($this->exactly(2))
            ->method('buildViolation')
            ->with($this->constraint->message, [
                '%fields%' => 'Field 1' . $this->translatedLabel . ', Field 2' . $this->translatedLabel
            ])
            ->willReturn($violationBuilder);

        $this->validator->validate($value, $this->constraint);
    }
}
