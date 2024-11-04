<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductHasPrice;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\ProductKitItemProductHasPriceValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemProductHasPriceValidatorTest extends ConstraintValidatorTestCase
{
    private FrontendProductPricesDataProvider|MockObject $frontendProductPricesDataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ProductKitItemProductHasPriceValidator
    {
        return new ProductKitItemProductHasPriceValidator($this->frontendProductPricesDataProvider);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemProductHasPrice::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $constraint = new ProductKitItemProductHasPrice();
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, ProductKitItemProduct::class));

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenKitItemHasNoProductUnit(): void
    {
        $constraint = new ProductKitItemProductHasPrice();

        $kitItem = new ProductKitItem();
        $product = (new ProductStub())
            ->setId(1);
        $kitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product)
            ->setKitItem($kitItem);

        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForProducts')
            ->with([$product])
            ->willReturn([
                1 => [
                    'set' => [
                        $this->createMock(ProductPriceInterface::class),
                    ],
                ]
            ]);

        $this->validator->validate($kitItemProduct, $constraint);

        $this->buildViolation($constraint->productHasNoPriceMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    public function testValidateWhenProductHasNoPrices(): void
    {
        $constraint = new ProductKitItemProductHasPrice();

        $productUnit = (new ProductUnit())
            ->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $product = (new ProductStub())
            ->setId(1);
        $kitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product)
            ->setKitItem($kitItem);

        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForProducts')
            ->with([$product])
            ->willReturn([
                1 => [
                    'set' => [
                        $this->createMock(ProductPriceInterface::class),
                    ],
                ]
            ]);

        $this->validator->validate($kitItemProduct, $constraint);

        $this->buildViolation($constraint->productHasNoPriceMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    public function testValidateWhenProductHasPrices(): void
    {
        $constraint = new ProductKitItemProductHasPrice();

        $productUnit = (new ProductUnit())
            ->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $product = (new ProductStub())
            ->setId(1);
        $kitItemProduct = (new ProductKitItemProduct())
            ->setProduct($product)
            ->setKitItem($kitItem);

        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForProducts')
            ->with([$product])
            ->willReturn([
                1 => [
                    'item' => [
                        $this->createMock(ProductPriceInterface::class),
                    ],
                ]
            ]);

        $this->validator->validate($kitItemProduct, $constraint);

        $this->assertNoViolation();
    }
}
