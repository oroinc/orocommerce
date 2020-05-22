<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\SkuRegex;
use Oro\Bundle\ProductBundle\Validator\Constraints\SkuRegexValidator;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SkuRegexValidatorTest extends \PHPUnit\Framework\TestCase
{
    private const PATTERN = '/^[-_a-zA-Z0-9]*$/';

    /**
     * @var SkuRegex|\PHPUnit\Framework\MockObject\MockObject
     */
    private $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var SkuRegexValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->constraint = $this->createMock(SkuRegex::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new SkuRegexValidator(self::PATTERN);
        $this->validator->initialize($this->context);
    }

    public function testValidate()
    {
        $value = 'abc_12-3';

        $this->context->expects($this->never())
            ->method('buildViolation');

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($value, new Regex(['pattern' => self::PATTERN]))
            ->willReturn(new ConstraintViolationList([]));

        $this->context->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateErrors()
    {
        $value = '~!@#$';

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($value, new Regex(['pattern' => self::PATTERN]))
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation(
                    "This value is not valid.",
                    "This value is not valid.",
                    ["{{value}}" => $value],
                    $value,
                    "",
                    $value
                )
            ]));

        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('getValidator')
            ->willReturn($validator);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($constraintViolationBuilder);

        $this->validator->validate($value, $this->constraint);
    }
}
