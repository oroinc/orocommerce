<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\Constraints\QuantityToOrder;
use Oro\Bundle\InventoryBundle\Validator\Constraints\QuantityToOrderValidator;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuantityToOrderValidatorTest extends ConstraintValidatorTestCase
{
    private QuantityToOrderValidatorService|\PHPUnit\Framework\MockObject\MockObject $quantityToOrderValidatorService;

    protected function setUp(): void
    {
        $this->quantityToOrderValidatorService = $this->createMock(QuantityToOrderValidatorService::class);

        parent::setUp();
    }

    protected function createValidator(): QuantityToOrderValidator
    {
        return new QuantityToOrderValidator($this->quantityToOrderValidatorService);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = new \stdClass();
        $this->expectExceptionObject(
            new UnexpectedValueException(
                $value,
                sprintf('%s & %s', ProductHolderInterface::class, QuantityAwareInterface::class)
            )
        );

        $this->validator->validate($value, new QuantityToOrder());
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, QuantityToOrder::class)
        );

        $this->validator->validate(
            new QuickAddRow(1, 'sku1', 42),
            $constraint
        );
    }

    public function testValidateWhenNoProduct(): void
    {
        $value = new QuickAddRow(1, 'sku1', 42);

        $this->validator->validate($value, new QuantityToOrder());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoViolations(): void
    {
        $product = new Product();
        $value = new QuickAddRow(1, 'sku1', 42);
        $value->setProduct($product);

        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn(false);

        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn(false);

        $this->validator->validate($value, new QuantityToOrder());

        $this->assertNoViolation();
    }

    public function testValidateWhenMinimumError(): void
    {
        $product = new Product();
        $value = new QuickAddRow(1, 'sku1', 42);
        $value->setProduct($product);

        $minimumError = 'minimum error';
        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn($minimumError);

        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn(false);

        $this->validator->validate($value, new QuantityToOrder());

        $this
            ->buildViolation($minimumError)
            ->setCode(QuantityToOrder::LESS_THAN_MIN_LIMIT)
            ->atPath('property.path.quantity')
            ->assertRaised();
    }

    public function testValidateWhenMaximumError(): void
    {
        $product = new Product();
        $value = new QuickAddRow(1, 'sku1', 42);
        $value->setProduct($product);

        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMinimumErrorIfInvalid')
            ->willReturn(false);

        $maximumError = 'maximum error';
        $this->quantityToOrderValidatorService
            ->expects(self::once())
            ->method('getMaximumErrorIfInvalid')
            ->willReturn($maximumError);

        $this->validator->validate($value, new QuantityToOrder());

        $this
            ->buildViolation($maximumError)
            ->setCode(QuantityToOrder::GREATER_THAN_MAX_LIMIT)
            ->atPath('property.path.quantity')
            ->assertRaised();
    }
}
