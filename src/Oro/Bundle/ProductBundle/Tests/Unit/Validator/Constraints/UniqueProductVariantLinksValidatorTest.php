<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UniqueProductVariantLinksValidatorTest extends \PHPUnit\Framework\TestCase
{
    const VARIANT_FIELD_KEY_COLOR = 'color';
    const VARIANT_FIELD_KEY_SIZE = 'size';
    const VARIANT_FIELD_KEY_SLIM_FIT = 'slim_fit';

    /**
     * @var UniqueProductVariantLinksValidator
     */
    protected $service;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->service = new UniqueProductVariantLinksValidator($propertyAccessor, $this->registry);
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

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeStringExistingProduct()
    {
        /** @var AbstractLazyCollection|\PHPUnit\Framework\MockObject\MockObject $variantLinks */
        $variantLinks = $this->createMock(AbstractLazyCollection::class);
        $variantLinks->expects($this->any())
            ->method('isInitialized')
            ->willReturn(false);
        $product = new Product();
        $product->setId(1);
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields([
            self::VARIANT_FIELD_KEY_SIZE,
            self::VARIANT_FIELD_KEY_COLOR,
        ]);
        $product->setVariantLinks($variantLinks);

        $simpleProducts = $this->prepareSimpleProducts([
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'M',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);

        $this->assertRepositoryCalls($product, $simpleProducts);
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

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeStringExistingProduct()
    {
        /** @var AbstractLazyCollection|\PHPUnit\Framework\MockObject\MockObject $variantLinks */
        $variantLinks = $this->createMock(AbstractLazyCollection::class);
        $variantLinks->expects($this->any())
            ->method('isInitialized')
            ->willReturn(false);
        $product = new Product();
        $product->setId(1);
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields([
            self::VARIANT_FIELD_KEY_SIZE,
            self::VARIANT_FIELD_KEY_COLOR,
        ]);
        $product->setVariantLinks($variantLinks);

        $simpleProducts = $this->prepareSimpleProducts([
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);

        $this->assertRepositoryCalls($product, $simpleProducts);
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

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
    private function prepareProduct(array $variantFields, array $variantLinkFields): Product
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $product->setVariantFields($variantFields);

        foreach ($this->prepareSimpleProducts($variantLinkFields) as $variantProduct) {
            $variantLink = new ProductVariantLink($product, $variantProduct);
            $product->addVariantLink($variantLink);
        }

        return $product;
    }

    /**
     * @param array $variantLinkFields
     * @return array
     */
    private function prepareSimpleProducts(array $variantLinkFields): array
    {
        $products = [];
        foreach ($variantLinkFields as $fields) {
            $simpleProduct = new Product();
            $simpleProduct->setType(Product::TYPE_SIMPLE);

            if (array_key_exists(self::VARIANT_FIELD_KEY_SIZE, $fields)) {
                $simpleProduct->setSize($fields[self::VARIANT_FIELD_KEY_SIZE]);
            }

            if (array_key_exists(self::VARIANT_FIELD_KEY_COLOR, $fields)) {
                $simpleProduct->setColor($fields[self::VARIANT_FIELD_KEY_COLOR]);
            }

            if (array_key_exists(self::VARIANT_FIELD_KEY_SLIM_FIT, $fields)) {
                $simpleProduct->setSlimFit((bool)$fields[self::VARIANT_FIELD_KEY_SLIM_FIT]);
            }
            $products[] = $simpleProduct;
        }

        return $products;
    }

    /**
     * @param \Oro\Bundle\ProductBundle\Entity\Product $product
     * @param array $simpleProducts
     */
    private function assertRepositoryCalls(Product $product, array $simpleProducts): void
    {
        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getSimpleProductsForConfigurableProduct')
            ->with($product)
            ->willReturn($simpleProducts);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(\Oro\Bundle\ProductBundle\Entity\Product::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\Oro\Bundle\ProductBundle\Entity\Product::class)
            ->willReturn($em);
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
