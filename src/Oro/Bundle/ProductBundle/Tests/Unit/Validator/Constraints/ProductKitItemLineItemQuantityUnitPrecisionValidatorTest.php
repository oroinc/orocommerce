<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemUnitPrecisionAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityUnitPrecisionValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitItemLineItemQuantityUnitPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    private RoundingServiceInterface|MockObject $roundingService;

    private UnitLabelFormatterInterface|MockObject $unitLabelFormatter;

    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->roundingService
            ->method('round')
            ->willReturnCallback(static fn ($value, $precision) => round($value, $precision));

        $this->unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $this->unitLabelFormatter
            ->method('format')
            ->willReturnCallback(fn (string $unitCode) => sprintf('%s formatted', $unitCode));

        parent::setUp();
    }

    protected function createValidator(): ProductKitItemLineItemQuantityUnitPrecisionValidator
    {
        return new ProductKitItemLineItemQuantityUnitPrecisionValidator(
            $this->roundingService,
            $this->unitLabelFormatter,
            new PropertyAccessor()
        );
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemLineItemQuantityUnitPrecision::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenNullValue(): void
    {
        $this->validator->validate(null, new ProductKitItemLineItemQuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, 'scalar'));

        $this->validator->validate($value, new ProductKitItemLineItemQuantityUnitPrecision());
    }

    public function testValidateWhenInvalidObject(): void
    {
        $value = 12.3456;
        $object = new \stdClass();
        $this->expectExceptionObject(
            new UnexpectedValueException($object, ProductKitItemLineItemInterface::class)
        );

        $this->setObject($object);
        $this->validator->validate($value, new ProductKitItemLineItemQuantityUnitPrecision());
    }

    public function testValidateWhenNoProductUnit(): void
    {
        $value = 12.3456;
        $kitItemLineItem = new ProductKitItemLineItemStub(42);
        $this->setObject($kitItemLineItem);

        $this->validator->validate($value, new ProductKitItemLineItemQuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProduct(): void
    {
        $value = 12.3456;
        $productUnit = (new ProductUnit())->setCode('item');
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit);
        $this->setObject($kitItemLineItem);

        $this->validator->validate($value, new ProductKitItemLineItemQuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProductUnitPrecision(): void
    {
        $value = 12.3456;
        $productUnit = (new ProductUnit())->setCode('item');
        $product = new ProductStub();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit)
            ->setProduct($product);
        $this->setObject($kitItemLineItem);

        $this->validator->validate($value, new ProductKitItemLineItemQuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testValidateWhenHasDefaultPrecision(): void
    {
        $value = 12.3456;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item')->setDefaultPrecision($precision);
        $product = new ProductStub();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit)
            ->setProduct($product);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision();
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ unit }}', '"' . 'item formatted' . '"')
            ->setParameter('{{ precision }}', $precision)
            ->setParameter('%count%', $precision)
            ->setCause($value)
            ->setCode($constraint::INVALID_PRECISION)
            ->assertRaised();
    }

    public function testValidateWhenHasDefaultPrecisionAndNoViolations(): void
    {
        $value = 12.34;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item')->setDefaultPrecision($precision);
        $product = new ProductStub();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit)
            ->setProduct($product);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenHasProductUnitPrecision(): void
    {
        $value = 12.3456;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision($precision);
        $product = (new ProductStub())
            ->setPrimaryUnitPrecision($productUnitPrecision);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit)
            ->setProduct($product);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision();
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ unit }}', '"' . 'item formatted' . '"')
            ->setParameter('{{ precision }}', $precision)
            ->setParameter('%count%', $precision)
            ->setCause($value)
            ->setCode($constraint::INVALID_PRECISION)
            ->assertRaised();
    }

    public function testValidateWhenHasProductUnitPrecisionAndNoViolations(): void
    {
        $value = 12.34;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item');
        $productUnitPrecision = (new ProductUnitPrecision())->setUnit($productUnit)->setPrecision($precision);
        $product = (new ProductStub())
            ->setPrimaryUnitPrecision($productUnitPrecision);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setUnit($productUnit)
            ->setProduct($product);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenHasUnitPrecisionPropertyPath(): void
    {
        $value = 12.3456;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item');
        $kitItemLineItem = (new ProductKitItemLineItemUnitPrecisionAwareStub(42))
            ->setUnit($productUnit)
            ->setProductUnitPrecision($precision);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision(
            ['unitPrecisionPropertyPath' => 'productUnitPrecision']
        );
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ unit }}', '"' . 'item formatted' . '"')
            ->setParameter('{{ precision }}', $precision)
            ->setParameter('%count%', $precision)
            ->setCause($value)
            ->setCode($constraint::INVALID_PRECISION)
            ->assertRaised();
    }

    public function testValidateWhenHasUnitPrecisionPropertyPathAndNoViolations(): void
    {
        $value = 12.34;
        $precision = 2;
        $productUnit = (new ProductUnit())->setCode('item');
        $kitItemLineItem = (new ProductKitItemLineItemUnitPrecisionAwareStub(42))
            ->setUnit($productUnit)
            ->setProductUnitPrecision($precision);
        $this->setObject($kitItemLineItem);

        $constraint = new ProductKitItemLineItemQuantityUnitPrecision(
            ['unitPrecisionPropertyPath' => 'productUnitPrecision']
        );
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
