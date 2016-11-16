<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimit;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimitValidator;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductQuantityToOrderLimitValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorService;

    /**
     * @var ProductQuantityToOrderLimitValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ProductQuantityToOrderLimit
     */
    protected $constraint;

    protected function setUp()
    {
        $this->validatorService = $this->getMockBuilder(QuantityToOrderValidatorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->constraint = new ProductQuantityToOrderLimit();
        $this->context = $this->getMock(ExecutionContextInterface::class);
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
        $product = new Product();
        $this
            ->validatorService
            ->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($product, $this->constraint);
    }

    public function testValidate()
    {
        $product = new Product();
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
            ->willReturn($this->getMock(ConstraintViolationBuilderInterface::class));
        $this->validator->validate($product, $this->constraint);
    }
}
