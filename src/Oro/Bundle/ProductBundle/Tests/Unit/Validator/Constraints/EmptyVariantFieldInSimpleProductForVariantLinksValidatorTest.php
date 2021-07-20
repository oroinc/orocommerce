<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinksValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmptyVariantFieldInSimpleProductForVariantLinksValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';
    const ID_FIELD = 'id';
    const MESSAGE = 'oro.product.product_variant_field.unique_variant_links_when_empty_variant_field_in_simple';

    /** @var ExecutionContextInterface| \PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmptyVariantFieldInSimpleProductForVariantLinksValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->validator = new EmptyVariantFieldInSimpleProductForVariantLinksValidator(
            $propertyAccessor,
            $this->registry
        );
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->context, $this->validator);
    }

    public function testValidateUnsupportedClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $this->validator->validate(new \stdClass(), new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testDoesNothingIfProductConfigurable()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $product->setType(Product::TYPE_CONFIGURABLE);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testDoesNothingIfNewProductHasNoParentVariantLinks()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testDoesNothingIfExistingProductHasNoParentVariantLinks()
    {
        $product = $this->prepareProduct(['id' => 1]);

        $this->assertAttributeInfoCalls($product, []);
        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testValidateWithOneErrorNewProduct()
    {
        $parentProduct1 = $this->prepareParentProduct(1, 'sku1', ['color', 'size']);
        $parentProduct2 = $this->prepareParentProduct(2, 'sku2', ['color']);

        $product = $this->prepareProduct(['size' => 'M'], [$parentProduct1, $parentProduct2]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                self::MESSAGE,
                [
                    '%variantField%' => 'color',
                    '%products%' => 'sku1, sku2'
                ]
            );

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testValidateWithOneErrorExistingProduct()
    {
        $product = $this->prepareProduct(['size' => 'M', 'id' => 1]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                self::MESSAGE,
                [
                    '%variantField%' => 'color',
                    '%products%' => 'sku1, sku2'
                ]
            );

        $attributeInfo = [
            ['id' => 1, 'sku' => 'sku1', 'variantFields' => ['color', 'size']],
            ['id' => 2, 'sku' => 'sku2', 'variantFields' => ['color']]
        ];
        $this->assertAttributeInfoCalls($product, $attributeInfo);

        $this->validator->validate($product, new EmptyVariantFieldInSimpleProductForVariantLinks());
    }

    public function testValidateWithTwoErrorsExistingProduct()
    {
        $product = $this->prepareProduct(['id' => 1]);

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
        $attributeInfo = [
            ['id' => 1, 'sku' => 'sku1', 'variantFields' => ['color', 'size']],
            ['id' => 2, 'sku' => 'sku2', 'variantFields' => ['color']]
        ];
        $this->assertAttributeInfoCalls($product, $attributeInfo);

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
            if ($variantField === self::ID_FIELD) {
                $product->setId($variantFieldsValue);
            }
        }

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

    private function assertAttributeInfoCalls(Product $product, array $attributeInfo): void
    {
        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getRequiredAttributesForSimpleProduct')
            ->with($product)
            ->willReturn($attributeInfo);
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

    /**
     * @param int $id
     * @param string $sku
     * @param array $variantFields
     * @return Product
     */
    private function prepareParentProduct($id, $sku, array $variantFields)
    {
        $product = new StubProduct();
        $product->setType(Product::TYPE_CONFIGURABLE)
            ->setId($id)
            ->setSku($sku)
            ->setVariantFields($variantFields);

        return $product;
    }
}
