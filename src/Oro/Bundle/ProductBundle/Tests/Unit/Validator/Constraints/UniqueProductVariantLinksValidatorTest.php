<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UniqueProductVariantLinksValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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
    protected function setUp(): void
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
    protected function tearDown(): void
    {
        unset($this->service, $this->context);
    }

    public function testNotProductValidate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );
        $this->service->validate(new \stdClass(), new UniqueProductVariantLinks());
    }

    public function testUnreachablePropertyException()
    {
        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'test';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not access property "test" for class "stdClass"');

        $this->service->validate(new \stdClass(), $constraint);
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

    public function testNotValidatePropertyWithNoVariantFields()
    {
        $product = $this->prepareProduct([], [
            [
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
            [
                self::VARIANT_FIELD_KEY_COLOR => 'Blue',
            ],
        ]);
        $variantLink = new ProductVariantLink($product, $this->getEntity(Product::class, ['id' => 555]));

        $this->context->expects($this->never())->method('addViolation');

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->service->validate($variantLink, $constraint);
    }

    public function testNotValidatePropertyNull()
    {
        $variantLink = new ProductVariantLink();

        $this->context->expects($this->never())->method('addViolation');

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->service->validate($variantLink, $constraint);
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

        $this->assertRepositoryCallsProductVariantLinks($product, $simpleProducts);
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

        $this->assertRepositoryCallsProductVariantLinks($product, $simpleProducts);
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
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('m', 'M'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
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
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                ],
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

    public function testPropertyAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                ],
            ]
        );
        $variantLink = new ProductVariantLink($product, $this->getEntity(Product::class, ['id' => 555]));

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->service->validate($variantLink, $constraint);
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
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true,
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
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
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false,
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false,
                ],
            ]
        );

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with((new UniqueProductVariantLinks())->uniqueRequiredMessage);

        $this->service->validate($product, new UniqueProductVariantLinks());
    }

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

    private function assertRepositoryCallsProductVariantLinks(Product $product, array $simpleProducts): void
    {
        $variantLink1 = $this->getEntity(ProductVariantLink::class, [
            'id' => 1,
            'product' => $simpleProducts[0],
            'parentProduct' => $product
        ]);

        $variantLink2 = $this->getEntity(ProductVariantLink::class, [
            'id' => 2,
            'product' => $simpleProducts[1],
            'parentProduct' => $product
        ]);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->willReturn([
                $variantLink1,
                $variantLink2
            ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(\Oro\Bundle\ProductBundle\Entity\ProductVariantLink::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(\Oro\Bundle\ProductBundle\Entity\ProductVariantLink::class)
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
