<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;

class UniqueProductVariantLinksValidatorTest extends \PHPUnit_Framework_TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';

    /**
     * @var UniqueProductVariantLinksValidator
     */
    protected $service;

    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->service = new UniqueProductVariantLinksValidator();
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
        $product->setHasVariants(false);

        $this->context->expects($this->never())->method('addViolation');

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUnique()
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

    public function testAddsViolationIfVariantFieldCombinationsAreNotUnique()
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
        $product->setHasVariants(true);
        $product->setVariantFields(['field1']);
        $variantLink = new ProductVariantLink($product);
        $product->addVariantLink($variantLink);

        $this->context->expects($this->never())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    /**
     * @param array $variantFields
     * @param array $variantLinkFields
     * @return StubProduct
     */
    private function prepareProduct(array $variantFields, array $variantLinkFields)
    {
        $product = new Product();
        $product->setHasVariants(true);
        $product->setVariantFields($variantFields);

        foreach ($variantLinkFields as $fields) {
            $variantProduct = new Product();

            if (array_key_exists(self::VARIANT_FIELD_KEY_SIZE, $fields)) {
                $variantProduct->setSize($fields[self::VARIANT_FIELD_KEY_SIZE]);
            }

            if (array_key_exists(self::VARIANT_FIELD_KEY_COLOR, $fields)) {
                $variantProduct->setColor($fields[self::VARIANT_FIELD_KEY_COLOR]);
            }

            $variantLink = new ProductVariantLink($product, $variantProduct);
            $product->addVariantLink($variantLink);
        }

        return $product;
    }
}
