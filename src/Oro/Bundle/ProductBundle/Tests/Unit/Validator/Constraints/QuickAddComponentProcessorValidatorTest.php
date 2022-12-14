<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

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
    private ComponentProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject $componentProcessorRegistry;

    protected function setUp(): void
    {
        $this->componentProcessorRegistry = $this->createMock(ComponentProcessorRegistry::class);

        parent::setUp();
    }

    protected function createValidator(): QuickAddComponentProcessorValidator
    {
        return new QuickAddComponentProcessorValidator($this->componentProcessorRegistry);
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
        $this->componentProcessorRegistry
            ->expects(self::once())
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
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('hasProcessor')
            ->with($value)
            ->willReturn(true);

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getProcessorByName')
            ->with($value)
            ->willReturn($componentProcessor);

        $componentProcessor
            ->expects(self::once())
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
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('hasProcessor')
            ->with($value)
            ->willReturn(true);

        $componentProcessor = $this->createMock(ComponentProcessorInterface::class);
        $this->componentProcessorRegistry
            ->expects(self::once())
            ->method('getProcessorByName')
            ->with($value)
            ->willReturn($componentProcessor);

        $componentProcessor
            ->expects(self::once())
            ->method('isAllowed')
            ->willReturn(true);

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
