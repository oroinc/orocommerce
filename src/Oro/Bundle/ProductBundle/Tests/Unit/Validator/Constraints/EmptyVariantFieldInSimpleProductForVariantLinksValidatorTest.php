<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinksValidator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmptyVariantFieldInSimpleProductForVariantLinksValidatorTest extends \PHPUnit_Framework_TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';
    const MESSAGE = 'oro.product.product_variant_field.unique_variant_links_when_empty_variant_field_in_simple';

    /** @var ExecutionContextInterface| \PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var EmptyVariantFieldInSimpleProductForVariantLinksValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new EmptyVariantFieldInSimpleProductForVariantLinksValidator($propertyAccessor);
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->context, $this->validator);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given
     */
    public function testValidateUnsupportedClass()
    {
        $this->validator->validate(new \stdClass(), new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testDoesNothingIfProductConfigurable()
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testDoesNothingIfProductHasNoParentVariantLinks()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testValidateWithOneError()
    {
        $parentProduct = $this->prepareParentProduct('sku1', ['color', 'size']);
        $parentProduct2 = $this->prepareParentProduct('sku2', ['color']);

        $product = $this->prepareProduct(['size' => 'M'], [$parentProduct, $parentProduct2]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                self::MESSAGE,
                [
                    '%variantField%' => 'color',
                    '%products%' => 'sku1, sku2'
                ]
            );

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testValidateWithTwoErrors()
    {
        $parentProduct = $this->prepareParentProduct('sku1', ['color', 'size']);
        $parentProduct2 = $this->prepareParentProduct('sku2', ['color']);

        $product = $this->prepareProduct([], [$parentProduct, $parentProduct2]);

        $this->context->expects($this->exactly(2))
            ->method('addViolation')
            ->withConsecutive(
                [
                    self::MESSAGE,
                    [
                        '%variantField%' => 'color',
                        '%products%' => 'sku1, sku2'
                    ]
                ],
                [
                    self::MESSAGE,
                    [
                        '%variantField%' => 'size',
                        '%products%' => 'sku1'
                    ]
                ]
            );

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    /**
     * @param array $parentProducts
     * @param array $variantFieldsValue
     * @return StubProduct
     */
    private function prepareProduct(array $variantFieldsValue, array $parentProducts = [])
    {
        $product = (new StubProduct())
            ->setType(Product::TYPE_SIMPLE);

        foreach ($variantFieldsValue as $variantField => $variantFieldValue) {
            if ($variantField === self::VARIANT_FIELD_KEY_SIZE) {
                $product->setSize($variantFieldValue);
            }

            if ($variantField === self::VARIANT_FIELD_KEY_COLOR) {
                $product->setColor($variantFieldValue);
            }
        }

        foreach ($parentProducts as $parentProduct) {
            $product->addParentVariantLink(new ProductVariantLink($parentProduct, $product));
        }

        return $product;
    }

    private function prepareParentProduct($sku, array $variantFields)
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE)
            ->setSku($sku)
            ->setVariantFields($variantFields);

        return $product;
    }
}
