<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\ShoppingListBundle\Validator\LineItemErrorsProvider;

class LineItemErrorsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var LineItemErrorsProvider
     */
    protected $lineItemErrorsProvider;

    protected function setUp()
    {
        $this->validator = $this->getMock(ValidatorInterface::class);
        $this->lineItemErrorsProvider = new LineItemErrorsProvider($this->validator);
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
        $constraintViolation = $this->getMock(ConstraintViolationInterface::class);
        $constraintViolation->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('testPath');
        /** @var ConstraintViolationListInterface|\PHPUnit_Framework_MockObject_MockObject $errorList */
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
}
