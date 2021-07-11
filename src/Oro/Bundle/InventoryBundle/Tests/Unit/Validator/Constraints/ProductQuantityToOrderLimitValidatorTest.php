<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints\Stub\ProductStub;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimit;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimitValidator;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductQuantityToOrderLimitValidatorTest extends ConstraintValidatorTestCase
{
    /** @var QuantityToOrderValidatorService|\PHPUnit\Framework\MockObject\MockObject */
    private $validatorService;

    protected function setUp(): void
    {
        $this->validatorService = $this->createMock(QuantityToOrderValidatorService::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ProductQuantityToOrderLimitValidator($this->validatorService);
    }

    public function testValidateEmptyValue()
    {
        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNoProductValue()
    {
        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutAddingConstraint()
    {
        $product = new ProductStub();
        $product->setId(1);
        $this->validatorService->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);

        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateIgnoredIfNoProductId()
    {
        $product = new ProductStub();
        $this->validatorService->expects($this->never())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);

        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidate()
    {
        $product = new ProductStub();
        $product->setId(1);
        $this->validatorService->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(true);

        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.minimumQuantityToOrder')
            ->assertRaised();
    }
}
