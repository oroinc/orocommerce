<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimit;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimitValidator;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ProductQuantityToOrderLimitValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validatorService;

    /**
     * @var ProductQuantityToOrderLimitValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var ProductQuantityToOrderLimit
     */
    protected $constraint;

    protected function setUp(): void
    {
        $this->validatorService = $this->getMockBuilder(QuantityToOrderValidatorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->constraint = new ProductQuantityToOrderLimit();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new ProductQuantityToOrderLimitValidator($this->validatorService);
        $this->validator->initialize($this->context);
    }

    public function testValidateEmptyValue()
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateNoProductValue()
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithoutAddingConstraint()
    {
        $product = new ProductStub();
        $product->setId(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($product, $this->constraint);
    }

    public function testValidateIgnoredIfNoProductId()
    {
        $product = new ProductStub();
        $this
            ->validatorService
            ->expects($this->never())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);
        $this->validator->validate($product, $this->constraint);
    }

    public function testValidate()
    {
        $product = new ProductStub();
        $product->setId(1);
        $this
            ->validatorService
            ->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(true);
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->willReturn($this->createMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($product, $this->constraint);
    }
}
