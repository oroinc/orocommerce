<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class UniqueProductVariantLinksValidatorTest extends \PHPUnit_Framework_TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';
    const VARIANT_FIELD_KEY_SLIM_FIT = 'slim_fit';

    /**
     * @var UniqueProductVariantLinksValidator
     */
    protected $service;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->context = $this->getMock(ExecutionContextInterface::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->service = new UniqueProductVariantLinksValidator($this->propertyAccessor);
        $this->service->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->service, $this->context);
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given
     */
    //@codingStandardsIgnoreEnd
    public function testNotProductValidate()
    {
        $this->service->validate(new \stdClass(), new UniqueProductVariantLinks());
    }

    public function testDoesNothingIfProductDoesNotHaveVariants()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeString()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ],
            ]
        );

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeString()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ],
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('m', 'M'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                ],
            ]
        );

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                ],
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeBoolean()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true,
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false,
                ],
            ]
        );

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeBoolean()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false,
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new StubEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new StubEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false,
                ],
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddViolationWhenVariantFieldsEmptyAndLinkPresent()
    {
        $product = $this->prepareProduct(
            [],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue',
                ]
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with($this->isType('string'), (new UniqueProductVariantLinks())->variantFieldRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testSkipIfProductIsMissingAndValidatedByNotBlank()
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields(['field1']);
        $variantLink = new ProductVariantLink($product);
        $product->addVariantLink($variantLink);

        $this->context->expects($this->never())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testAddViolationWhenProductHasNoFilledField()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();

        $this->context->expects($this->exactly(2))
            ->method('addViolation')
            ->with($constraint->variantLinkHasNoFilledFieldMessage);

        $this->service->validate($product, $constraint);
    }

    /**
     * @param array $variantFields
     * @param array $variantLinkFields
     * @return Product
     */
    private function prepareProduct(array $variantFields, array $variantLinkFields)
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields($variantFields);

        foreach ($variantLinkFields as $fields) {
            $variantProduct = new Product();

            if (array_key_exists(self::VARIANT_FIELD_KEY_SIZE, $fields)) {
                $variantProduct->setSize($fields[self::VARIANT_FIELD_KEY_SIZE]);
            }

            if (array_key_exists(self::VARIANT_FIELD_KEY_COLOR, $fields)) {
                $variantProduct->setColor($fields[self::VARIANT_FIELD_KEY_COLOR]);
            }

            if (array_key_exists(self::VARIANT_FIELD_KEY_SLIM_FIT, $fields)) {
                $variantProduct->setSlimFit((bool)$fields[self::VARIANT_FIELD_KEY_SLIM_FIT]);
            }

            $variantLink = new ProductVariantLink($product, $variantProduct);
            $product->addVariantLink($variantLink);
        }

        return $product;
    }
}
