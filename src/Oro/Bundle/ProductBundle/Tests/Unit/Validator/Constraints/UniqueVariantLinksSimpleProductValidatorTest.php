<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProductValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UniqueVariantLinksSimpleProductValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $uniqueVariantLinksProductValidatorMock;

    /** @var UniqueVariantLinksSimpleProductValidator */
    private $uniqueVariantLinksSimpleProductValidator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
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
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->uniqueVariantLinksSimpleProductValidator->setRegistry($this->registry);
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

    public function testValidateWithoutErrorsExistingEntity()
    {
        $parentProduct = (new Product())->setType(Product::TYPE_CONFIGURABLE);
        $product = $this->prepareProduct([], 1);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->once())
            ->method('validate')
            ->with($parentProduct, new UniqueProductVariantLinks())
            ->willReturn(new ConstraintViolationList());

        $this->assertRepositoryCalls($product, [$parentProduct]);

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

    public function testValidateWitErrorsOnlyForOneProductExistingEntity()
    {
        $parentProduct = (new Product())->setSku('sku1')->setType(Product::TYPE_CONFIGURABLE);
        $parentProduct2 = (new Product())->setSku('sku2')->setType(Product::TYPE_CONFIGURABLE);

        $product = $this->prepareProduct([], 1);

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

        $this->assertRepositoryCalls($product, [$parentProduct, $parentProduct2]);
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
     * @param null|int $id
     * @return Product
     */
    private function prepareProduct(array $parentProducts = [], $id = null)
    {
        $product = (new StubProduct())
            ->setType(Product::TYPE_SIMPLE)
            ->setId($id);

        /** @var AbstractLazyCollection|\PHPUnit\Framework\MockObject\MockObject $parentVariantLinks */
        $parentVariantLinks = $this->createMock(AbstractLazyCollection::class);
        $parentVariantLinks->expects($this->any())
            ->method('isInitialized')
            ->willReturn(!empty($parentProducts));
        if ($parentProducts) {
            $collection = new ArrayCollection();
            foreach ($parentProducts as $parentProduct) {
                $variantLink = new ProductVariantLink($parentProduct, $product);
                $collection->add($variantLink);
            }
            $parentVariantLinks->expects($this->any())
                ->method('getIterator')
                ->willReturn($collection);
        }
        $product->setParentVariantLinks($parentVariantLinks);

        return $product;
    }

    /**
     * @param Product $product
     * @param array $configurableProducts
     */
    private function assertRepositoryCalls(Product $product, array $configurableProducts)
    {
        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getParentProductsForSimpleProduct')
            ->with($product)
            ->willReturn($configurableProducts);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($em);
    }
}
