<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitShippingCalculationMethod;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitShippingCalculationMethodValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitShippingCalculationMethodValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new ProductKitShippingCalculationMethodValidator();
    }

    public function testValidateWithWrongConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "%s" given',
            ProductKitShippingCalculationMethod::class,
            NotNull::class
        ));

        $constraint = new NotNull();
        $this->validator->validate(new Product(), $constraint);
    }

    public function testValidateWithNotTheProductEntity(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new ProductKitShippingCalculationMethod([
            'allowedShippingCalculationMethods' => $this->getAllowedMethods()
        ]);

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWithEmptyProductKitShippingCalculationMethod(): void
    {
        $value = new Product();

        $constraint = new ProductKitShippingCalculationMethod([
            'allowedShippingCalculationMethods' => $this->getAllowedMethods()
        ]);

        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithWrongSimpleProductKitShippingCalculationMethod(): void
    {
        $value = new Product();
        $value->setType(Product::TYPE_SIMPLE);
        $value->setKitShippingCalculationMethod(Product::KIT_SHIPPING_ALL);

        $constraint = new ProductKitShippingCalculationMethod([
            'allowedShippingCalculationMethods' => $this->getAllowedMethods()
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.kitShippingCalculationMethod')
            ->setParameters([
                '{{ method }}' => sprintf('"%s"', Product::KIT_SHIPPING_ALL),
                '{{ type }}' => Product::TYPE_SIMPLE,
                '{{ allowed_methods }}' => 'null',
            ])
            ->setCode(ProductKitShippingCalculationMethod::METHOD_NOT_ALLOWED_ERROR)
            ->assertRaised();
    }

    public function testValidateWithWrongKitProductKitShippingCalculationMethod(): void
    {
        $value = new Product();
        $value->setType(Product::TYPE_KIT);
        $value->setKitShippingCalculationMethod('kit_wrong_shipping');
        $allowedMethods = $this->getAllowedMethods();

        $constraint = new ProductKitShippingCalculationMethod(['allowedShippingCalculationMethods' => $allowedMethods]);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.kitShippingCalculationMethod')
            ->setParameters([
                '{{ method }}' => '"kit_wrong_shipping"',
                '{{ type }}' => Product::TYPE_KIT,
                '{{ allowed_methods }}' => sprintf('"%s", "%s", "%s"', ...$allowedMethods),
            ])
            ->setCode(ProductKitShippingCalculationMethod::METHOD_NOT_ALLOWED_ERROR)
            ->assertRaised();
    }

    public function testValidateWithCorrectData(): void
    {
        $value = new Product();
        $value->setType(Product::TYPE_KIT);
        $value->setKitShippingCalculationMethod(Product::KIT_SHIPPING_ALL);
        $allowedMethods = $this->getAllowedMethods();

        $constraint = new ProductKitShippingCalculationMethod(['allowedShippingCalculationMethods' => $allowedMethods]);
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    private function getAllowedMethods(): array
    {
        return [
            Product::KIT_SHIPPING_ALL,
            Product::KIT_SHIPPING_ONLY_ITEMS,
            Product::KIT_SHIPPING_ONLY_PRODUCT
        ];
    }
}
