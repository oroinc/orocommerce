<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductTypeValidator;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductTypeValidator
    {
        return new ProductTypeValidator();
    }

    public function testGetRequiredOptions(): void
    {
        $constraint = new ProductType(['allowedTypes' => ['simple']]);

        self::assertEquals(['allowedTypes'], $constraint->getRequiredOptions());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductType::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new ProductType(['allowedTypes' => ['simple']]);
        $this->validator->validate($value, $constraint);
    }

    public function testValidateUnsupportedWhenProductNull(): void
    {
        $constraint = new ProductType(['allowedTypes' => ['kit']]);

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidType(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $constraint = new ProductType(['allowedTypes' => ['kit']]);
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"' . Product::TYPE_SIMPLE . '"')
            ->setParameter('{{ allowed_types }}', '"kit"')
            ->setParameter('%count%', 1)
            ->atPath('property.path.type')
            ->setCode(ProductType::TYPE_NOT_ALLOWED_ERROR)
            ->assertRaised();
    }

    public function testValidateWhenInvalidTypeAndMultipleAllowed(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $constraint = new ProductType(['allowedTypes' => ['kit', 'simple']]);
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ type }}', '"' . Product::TYPE_CONFIGURABLE . '"')
            ->setParameter('{{ allowed_types }}', '"kit", "simple"')
            ->setParameter('%count%', 2)
            ->atPath('property.path.type')
            ->setCode(ProductType::TYPE_NOT_ALLOWED_ERROR)
            ->assertRaised();
    }

    public function testValidate(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_KIT);

        $constraint = new ProductType(['allowedTypes' => ['kit']]);
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }
}
