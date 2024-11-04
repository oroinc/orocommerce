<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorRegistry;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessor;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessorValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuickAddComponentProcessorValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ComponentProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ComponentProcessorRegistry::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): QuickAddComponentProcessorValidator
    {
        return new QuickAddComponentProcessorValidator($this->processorRegistry);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, 'string'));

        $this->validator->validate($value, new QuickAddComponentProcessor());
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, QuickAddComponentProcessor::class)
        );

        $this->validator->validate('sample_name', $constraint);
    }

    public function testValidateWhenNoComponentProcessor(): void
    {
        $constraint = new QuickAddComponentProcessor();

        $value = 'sample_name';
        $this->processorRegistry->expects(self::once())
            ->method('hasProcessor')
            ->with($value)
            ->willReturn(false);

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters(['{{ name }}' => $value])
            ->setCode(QuickAddComponentProcessor::NOT_AVAILABLE_PROCESSOR)
            ->assertRaised();
    }

    public function testValidateWhenComponentProcessorNotAllowed(): void
    {
        $constraint = new QuickAddComponentProcessor();

        $value = 'sample_name';
        $this->processorRegistry->expects(self::once())
            ->method('hasProcessor')
            ->with($value)
            ->willReturn(true);

        $processor = $this->createMock(ComponentProcessorInterface::class);
        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($value)
            ->willReturn($processor);

        $processor->expects(self::once())
            ->method('isAllowed')
            ->willReturn(false);

        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters(['{{ name }}' => $value])
            ->setCode(QuickAddComponentProcessor::NOT_AVAILABLE_PROCESSOR)
            ->assertRaised();
    }

    public function testValidateWhenNoViolations(): void
    {
        $constraint = new QuickAddComponentProcessor();

        $value = 'sample_name';
        $this->processorRegistry->expects(self::once())
            ->method('hasProcessor')
            ->with($value)
            ->willReturn(true);

        $processor = $this->createMock(ComponentProcessorInterface::class);
        $this->processorRegistry->expects(self::once())
            ->method('getProcessor')
            ->with($value)
            ->willReturn($processor);

        $processor->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
