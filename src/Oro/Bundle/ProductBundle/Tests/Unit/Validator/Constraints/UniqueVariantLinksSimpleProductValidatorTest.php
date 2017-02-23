<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProductValidator;

class UniqueVariantLinksSimpleProductValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $uniqueVariantLinksProductValidatorMock;

    /** @var UniqueVariantLinksSimpleProductValidator */
    private $uniqueVariantLinksSimpleProductValidator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->uniqueVariantLinksProductValidatorMock = $this->createMock(ValidatorInterface::class);
        $this->uniqueVariantLinksSimpleProductValidator = new UniqueVariantLinksSimpleProductValidator(
            $this->uniqueVariantLinksProductValidatorMock
        );

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->uniqueVariantLinksSimpleProductValidator->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->uniqueVariantLinksProductValidatorMock,
            $this->uniqueVariantLinksSimpleProductValidator,
            $this->context
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given
     */
    public function testValidateUnsupportedClass()
    {
        $this->uniqueVariantLinksSimpleProductValidator->validate(
            new \stdClass(),
            new UniqueVariantLinksSimpleProduct()
        );
    }

    public function testSkipIfProductConfigurable()
    {
        $product = (new Product())->setType(Product::TYPE_CONFIGURABLE);
        $this->context->expects($this->never())->method('addViolation');

        $this->uniqueVariantLinksSimpleProductValidator->validate($product, new UniqueVariantLinksSimpleProduct());
    }

    public function testSkipIfProductHasNoParentVariantLinks()
    {
        $product = (new Product())->setType(Product::TYPE_SIMPLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->uniqueVariantLinksSimpleProductValidator->validate($product, new UniqueVariantLinksSimpleProduct());
    }

    public function testValidateWithoutErrors()
    {
        $parentProduct = (new Product())->setType(Product::TYPE_CONFIGURABLE);
        $product = $this->prepareProduct([$parentProduct]);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->once())
            ->method('validate')
            ->with($parentProduct, new UniqueProductVariantLinks())
            ->willReturn(new ConstraintViolationList());

        $this->context->expects($this->never())->method('addViolation');

        $this->uniqueVariantLinksSimpleProductValidator->validate($product, new UniqueVariantLinksSimpleProduct());
    }

    public function testValidateWitErrors()
    {
        $parentProduct = (new Product())->setSku('sku1')->setType(Product::TYPE_CONFIGURABLE);
        $parentProduct2 = (new Product())->setSku('sku2')->setType(Product::TYPE_CONFIGURABLE);

        $product = $this->prepareProduct([$parentProduct, $parentProduct2]);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$parentProduct, new UniqueProductVariantLinks()],
                [$parentProduct2, new UniqueProductVariantLinks()]
            )
            ->willReturn(new ConstraintViolationList([new ConstraintViolation('message', '', [], '', '', '')]));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                'oro.product.product_variant_field.unique_variants_combination_simple_product.message',
                [
                    '%products%' => 'sku1, sku2'
                ]
            );

        $this->uniqueVariantLinksSimpleProductValidator->validate($product, new UniqueVariantLinksSimpleProduct());
    }

    public function testValidateWitErrorsOnlyForOneProduct()
    {
        $parentProduct = (new Product())->setSku('sku1')->setType(Product::TYPE_CONFIGURABLE);
        $parentProduct2 = (new Product())->setSku('sku2')->setType(Product::TYPE_CONFIGURABLE);

        $product = $this->prepareProduct([$parentProduct, $parentProduct2]);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->exactly(2))
            ->method('validate')
            ->withConsecutive(
                [$parentProduct, new UniqueProductVariantLinks()],
                [$parentProduct2, new UniqueProductVariantLinks()]
            )
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList([new ConstraintViolation('message', '', [], '', '', '')]),
                new ConstraintViolationList()
            );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                'oro.product.product_variant_field.unique_variants_combination_simple_product.message',
                [
                    '%products%' => 'sku1'
                ]
            );

        $this->uniqueVariantLinksSimpleProductValidator->validate($product, new UniqueVariantLinksSimpleProduct());
    }

    /**
     * @param array $parentProducts
     * @return Product
     */
    private function prepareProduct(array $parentProducts = [])
    {
        $product = (new Product())
            ->setType(Product::TYPE_SIMPLE);

        foreach ($parentProducts as $parentProduct) {
            $product->addParentVariantLink(new ProductVariantLink($parentProduct, $product));
        }

        return $product;
    }
}
