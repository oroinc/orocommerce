<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator;

use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LineItemViolationsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var LineItemViolationsProvider
     */
    protected $lineItemErrorsProvider;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->lineItemErrorsProvider = new LineItemViolationsProvider($this->validator);
    }

    public function testIsLineItemListValidReturnFalse()
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn(['xxx']);
        $this->assertFalse($this->lineItemErrorsProvider->isLineItemListValid([]));
    }

    public function testIsLineItemListValidReturnTrue()
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn([]);
        $this->assertTrue($this->lineItemErrorsProvider->isLineItemListValid([]));
    }

    public function testGetLineItemErrorsReturnEmptyArray()
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn([]);
        $this->assertEmpty($this->lineItemErrorsProvider->getLineItemErrors([]));
    }

    public function testGetLineItemErrorsReturnIndexedErrors()
    {
        $constraintViolation = $this->createMock(ConstraintViolation::class);
        $constraintViolation->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('testPath');

        $constraintViolation->expects($this->once())
            ->method('getCause')
            ->willReturn('error');

        /** @var ConstraintViolationListInterface|\PHPUnit\Framework\MockObject\MockObject $errorList */
        $errorList = new ConstraintViolationList();
        $errorList->add($constraintViolation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($errorList);

        $errors = $this->lineItemErrorsProvider->getLineItemErrors([]);
        $this->assertArrayHasKey('testPath', $errors);
        $this->assertCount(1, $errors['testPath']);
        $this->assertSame($errors['testPath'][0], $constraintViolation);
    }

    public function testGetLineItemErrorsReturnIndexedWarning()
    {
        $constraintViolation = $this->createMock(ConstraintViolation::class);
        $constraintViolation->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('testPath');

        $constraintViolation->expects($this->exactly(2))
            ->method('getCause')
            ->willReturn('warning');

        /** @var ConstraintViolationListInterface|\PHPUnit\Framework\MockObject\MockObject $errorList */
        $errorList = new ConstraintViolationList();
        $errorList->add($constraintViolation);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($errorList);

        $errors = $this->lineItemErrorsProvider->getLineItemErrors([]);
        $this->assertEquals([], $errors);
        $warnings = $this->lineItemErrorsProvider->getLineItemWarnings([]);
        $this->assertArrayHasKey('testPath', $warnings);
        $this->assertCount(1, $warnings['testPath']);
        $this->assertSame($warnings['testPath'][0], $constraintViolation);
    }

    public function testGetLineItemViolationListsReturnIndexedErrors(): void
    {
        $warning = $this->createMock(ConstraintViolation::class);
        $warning->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('warningPath');

        $error = $this->createMock(ConstraintViolation::class);
        $error->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('errorPath');

        $errorList = new ConstraintViolationList();
        $errorList->add($warning);
        $errorList->add($error);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($errorList);

        $errors = $this->lineItemErrorsProvider->getLineItemViolationLists([]);
        $this->assertArrayHasKey('warningPath', $errors);
        $this->assertCount(1, $errors['warningPath']);
        $this->assertSame($errors['warningPath'][0], $warning);
        $this->assertArrayHasKey('errorPath', $errors);
        $this->assertCount(1, $errors['errorPath']);
        $this->assertSame($errors['errorPath'][0], $error);
    }
}
