<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimit;
use Oro\Bundle\InventoryBundle\Validator\Constraints\ProductQuantityToOrderLimitValidator;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
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

    protected function createValidator(): ProductQuantityToOrderLimitValidator
    {
        return new ProductQuantityToOrderLimitValidator($this->validatorService);
    }

    public function testValidateForUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Product(), $this->createMock(Constraint::class));
    }

    public function testValidateWhenValueIsNotProductEntity()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new ProductQuantityToOrderLimit());
    }

    public function testValidateWhenValueIsNull()
    {
        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForNewProduct()
    {
        $product = new Product();

        $this->validatorService->expects($this->never())
            ->method('isMaxLimitLowerThenMinLimit');

        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutAddingConstraint()
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

        $this->validatorService->expects($this->once())
            ->method('isMaxLimitLowerThenMinLimit')
            ->with($product)
            ->willReturn(false);

        $constraint = new ProductQuantityToOrderLimit();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidate()
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);

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
