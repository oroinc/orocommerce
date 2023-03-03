<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeFamilyStub;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVariantLinksValidatorTest extends ConstraintValidatorTestCase
{
    private const VARIANT_FIELD_KEY_COLOR = 'color';
    private const VARIANT_FIELD_KEY_SIZE = 'size';
    private const VARIANT_FIELD_KEY_SLIM_FIT = 'slim_fit';

    protected function createValidator(): ProductVariantLinksValidator
    {
        return new ProductVariantLinksValidator(PropertyAccess::createPropertyAccessor());
    }

    public function testGetTargets(): void
    {
        $constraint = new ProductVariantLinks();
        self::assertEquals(
            [Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT],
            $constraint->getTargets()
        );
    }

    public function testValidateUnsupportedClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new ProductVariantLinks();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testDoesNothingIfProductDoesNotHaveVariants(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $constraint = new ProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddViolationWhenVariantFieldsEmptyAndLinkPresent(): void
    {
        $defaultAttributeFamily = new AttributeFamilyStub();
        $product = $this->prepareProduct(
            [],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue'
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Black'
                ]
            ],
            $defaultAttributeFamily,
            $defaultAttributeFamily
        );

        $constraint = new ProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->variantFieldRequiredMessage)
            ->atPath('property.path.variantFields')
            ->assertRaised();
    }

    public function testSkipIfProductIsMissingAndValidatedByNotBlank(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields(['field1']);
        $variantLink = new ProductVariantLink($product);
        $product->addVariantLink($variantLink);

        $constraint = new ProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddViolationWhenProductHasNoFilledField(): void
    {
        $defaultAttributeFamily = new AttributeFamilyStub();
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Black'
                ]
            ],
            $defaultAttributeFamily,
            $defaultAttributeFamily
        );

        $constraint = new ProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->variantLinkHasNoFilledFieldMessage)
            ->setParameters(['%product_sku%' => '', '%fields%' => 'size, color, slim_fit'])
            ->assertRaised();
    }

    public function testUnreachablePropertyException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not access property "test" for class "stdClass"');

        $constraint = new ProductVariantLinks();
        $constraint->property = 'test';
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testNotValidatePropertyNull(): void
    {
        $variantLink = new ProductVariantLink();

        $constraint = new ProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->validator->validate($variantLink, $constraint);

        $this->assertNoViolation();
    }

    public function testAddViolationWhenProductByPropertyHasNoFilledField(): void
    {
        $defaultAttributeFamily = new AttributeFamilyStub();
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Black'
                ]
            ],
            $defaultAttributeFamily,
            $defaultAttributeFamily
        );
        $simpleProduct = new Product();
        $simpleProduct->setAttributeFamily(new AttributeFamilyStub());
        $variantLink = new ProductVariantLink($product, $simpleProduct);

        $constraint = new ProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->validator->validate($variantLink, $constraint);

        $this->buildViolation($constraint->variantLinkHasNoFilledFieldMessage)
            ->setParameters(['%product_sku%' => '', '%fields%' => 'size, color, slim_fit'])
            ->assertRaised();
    }

    /**
     * @dataProvider getProductAndVariantLinksWithDifferentFamilyDataProvider
     */
    public function testAddViolationWhenProductHasVariantLinksFromDifferentFamily(
        AttributeFamily $parentProductAttributeFamily = null,
        AttributeFamily $variantLinkAttributeFamily = null
    ): void {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Black'
                ]
            ],
            $parentProductAttributeFamily,
            $variantLinkAttributeFamily
        );

        $constraint = new ProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->variantLinkBelongsAnotherFamilyMessage)
            ->setParameter('%products_sku%', ', ')
            ->assertRaised();
    }

    public function getProductAndVariantLinksWithDifferentFamilyDataProvider(): array
    {
        $productAttributeFamily = new AttributeFamilyStub();
        $productAttributeFamily->setCode('test1');
        $simpleProductAttributeFamily = new AttributeFamilyStub();
        $simpleProductAttributeFamily->setCode('test2');

        return [
            'empty parent product family' => [
                'parentProductAttributeFamily' => null,
                'variantLinkAttributeFamily' => $simpleProductAttributeFamily,
            ],
            'empty simple product family' => [
                'parentProductAttributeFamily' => $productAttributeFamily,
                'variantLinkAttributeFamily' => null,
            ],
            'different parent product and simple product attribute family' => [
                'parentProductAttributeFamily' => $productAttributeFamily,
                'variantLinkAttributeFamily' => $simpleProductAttributeFamily,
            ],
        ];
    }

    private function prepareProduct(
        array $variantFields,
        array $variantLinkFields,
        AttributeFamily $parentProductAttributeFamily = null,
        AttributeFamily $variantLinkAttributeFamily = null
    ): Product {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields($variantFields);
        if ($parentProductAttributeFamily) {
            $product->setAttributeFamily($parentProductAttributeFamily);
        }

        foreach ($variantLinkFields as $fields) {
            $variantProduct = new Product();
            if ($variantLinkAttributeFamily) {
                $variantProduct->setAttributeFamily($variantLinkAttributeFamily);
            }
            if (array_key_exists(self::VARIANT_FIELD_KEY_SIZE, $fields)) {
                $variantProduct->setSize($fields[self::VARIANT_FIELD_KEY_SIZE]);
            }
            if (array_key_exists(self::VARIANT_FIELD_KEY_COLOR, $fields)) {
                $variantProduct->setColor($fields[self::VARIANT_FIELD_KEY_COLOR]);
            }
            if (array_key_exists(self::VARIANT_FIELD_KEY_SLIM_FIT, $fields)) {
                $variantProduct->setSlimFit((bool)$fields[self::VARIANT_FIELD_KEY_SLIM_FIT]);
            }
            $product->addVariantLink(new ProductVariantLink($product, $variantProduct));
        }

        return $product;
    }
}
