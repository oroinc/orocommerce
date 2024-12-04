<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Validator\Constraints\DefaultProductVariant;
use Oro\Bundle\ProductBundle\Validator\Constraints\DefaultProductVariantValidator;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class DefaultProductVariantValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DefaultProductVariantValidator
    {
        return new DefaultProductVariantValidator();
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, DefaultProductVariant::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new DefaultProductVariant();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnsupportedWhenProductNull(): void
    {
        $constraint = new DefaultProductVariant();

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateNonConfigurableProduct(): void
    {
        $constraint = new DefaultProductVariant();

        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateEmptyDefaultVariant(): void
    {
        $constraint = new DefaultProductVariant();

        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateDefaultVariantBelongToProductVariants(): void
    {
        $constraint = new DefaultProductVariant();

        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        $product1 = new Product();
        $product1->setType(Product::TYPE_SIMPLE);
        $product2 = new Product();
        $product2->setType(Product::TYPE_SIMPLE);

        $configurableProduct->addVariantLink(new ProductVariantLink($configurableProduct, $product1));
        $configurableProduct->addVariantLink(new ProductVariantLink($configurableProduct, $product2));
        $configurableProduct->setDefaultVariant($product2);

        $this->validator->validate($configurableProduct, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateDefaultVariantNotBelongToProductVariants(): void
    {
        $constraint = new DefaultProductVariant();

        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        $product1 = new Product();
        $product1->setType(Product::TYPE_SIMPLE);
        $product2 = new Product();
        $product2->setType(Product::TYPE_SIMPLE);
        $product3 = new Product();
        $product3->setType(Product::TYPE_SIMPLE);

        $configurableProduct->addVariantLink(new ProductVariantLink($configurableProduct, $product1));
        $configurableProduct->addVariantLink(new ProductVariantLink($configurableProduct, $product2));
        $configurableProduct->setDefaultVariant($product3);

        $this->validator->validate($configurableProduct, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.defaultVariant')
            ->assertRaised();
    }
}
