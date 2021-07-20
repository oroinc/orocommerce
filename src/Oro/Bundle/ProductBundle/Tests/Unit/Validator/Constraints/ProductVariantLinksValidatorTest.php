<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\AttributeFamilyStub;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksValidator;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVariantLinksValidatorTest extends \PHPUnit\Framework\TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';
    const VARIANT_FIELD_KEY_SLIM_FIT = 'slim_fit';

    /**
     * @var ProductVariantLinksValidator
     */
    protected $service;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->service = new ProductVariantLinksValidator($propertyAccessor);
        $this->service->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->service, $this->context);
    }

    public function testValidateUnsupportedClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $this->service->validate(new \stdClass(), new ProductVariantLinks());
    }

    public function testDoesNothingIfProductDoesNotHaveVariants(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new ProductVariantLinks());
    }

    public function testAddViolationWhenVariantFieldsEmptyAndLinkPresent(): void
    {
        $defaultAttributeFamily = new AttributeFamilyStub();
        $product = $this->prepareProduct(
            [],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Black',
                ]
            ],
            $defaultAttributeFamily,
            $defaultAttributeFamily
        );

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with((new ProductVariantLinks())->variantFieldRequiredMessage)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->with($this->isType('string'))
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->service->validate($product, new ProductVariantLinks());
    }

    public function testSkipIfProductIsMissingAndValidatedByNotBlank(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields(['field1']);
        $variantLink = new ProductVariantLink($product);
        $product->addVariantLink($variantLink);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new ProductVariantLinks());
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
                    self::VARIANT_FIELD_KEY_COLOR => 'Black',
                ]
            ],
            $defaultAttributeFamily,
            $defaultAttributeFamily
        );

        $constraint = new ProductVariantLinks();

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->variantLinkHasNoFilledFieldMessage);

        $this->service->validate($product, $constraint);
    }

    public function testUnreachablePropertyException(): void
    {
        $constraint = new ProductVariantLinks();
        $constraint->property = 'test';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not access property "test" for class "stdClass"');

        $this->service->validate(new \stdClass(), $constraint);
    }

    public function testNotValidatePropertyNull(): void
    {
        $variantLink = new ProductVariantLink();

        $this->context->expects($this->never())->method('addViolation');

        $constraint = new ProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->service->validate($variantLink, $constraint);
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
                    self::VARIANT_FIELD_KEY_COLOR => 'Black',
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

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->variantLinkHasNoFilledFieldMessage);

        $this->service->validate($variantLink, $constraint);
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
                    self::VARIANT_FIELD_KEY_COLOR => 'Black',
                ]
            ],
            $parentProductAttributeFamily,
            $variantLinkAttributeFamily
        );

        $constraint = new ProductVariantLinks();

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($constraint->variantLinkBelongsAnotherFamilyMessage);

        $this->service->validate($product, $constraint);
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
