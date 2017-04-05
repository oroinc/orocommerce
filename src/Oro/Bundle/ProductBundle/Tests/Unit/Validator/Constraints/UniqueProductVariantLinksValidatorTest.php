<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->service = new UniqueProductVariantLinksValidator($propertyAccessor);
        $this->service->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->service, $this->context);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given
     */
    public function testNotProductValidate()
    {
        $this->service->validate(new \stdClass(), new UniqueProductVariantLinks());
    }

    public function testNotValidateWithNoVariantFields()
    {
        $product = $this->prepareProduct([], [
            [
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);

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

    public function testDoesNothingIfSimpleProduct()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testDoesNothingIfProductVariantHasNoProduct()
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $variantLink = new ProductVariantLink($product, null);
        $product->addVariantLink($variantLink);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }
}
