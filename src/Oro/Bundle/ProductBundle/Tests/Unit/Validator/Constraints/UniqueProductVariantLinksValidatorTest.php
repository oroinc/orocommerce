<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UniqueProductVariantLinksValidatorTest extends ConstraintValidatorTestCase
{
    private const VARIANT_FIELD_KEY_COLOR = 'color';
    private const VARIANT_FIELD_KEY_SIZE = 'size';
    private const VARIANT_FIELD_KEY_SLIM_FIT = 'slim_fit';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        parent::setUp();
    }

    protected function createValidator(): UniqueProductVariantLinksValidator
    {
        return new UniqueProductVariantLinksValidator(
            PropertyAccess::createPropertyAccessor(),
            $this->registry
        );
    }

    public function testGetTargets()
    {
        $constraint = new UniqueProductVariantLinks();
        self::assertEquals(
            [Constraint::CLASS_CONSTRAINT, Constraint::PROPERTY_CONSTRAINT],
            $constraint->getTargets()
        );
    }

    public function testNotProductValidate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testUnreachablePropertyException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not access property "test" for class "stdClass"');

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'test';
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testNotValidateWithNoVariantFields()
    {
        $product = $this->prepareProduct([], [
            [self::VARIANT_FIELD_KEY_COLOR => 'Blue'],
            [self::VARIANT_FIELD_KEY_COLOR => 'Blue']
        ]);

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testNotValidatePropertyWithNoVariantFields()
    {
        $product = $this->prepareProduct([], [
            [self::VARIANT_FIELD_KEY_COLOR => 'Blue'],
            [self::VARIANT_FIELD_KEY_COLOR => 'Blue']
        ]);
        $variantLink = new ProductVariantLink($product, $this->getProduct(555));

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->validator->validate($variantLink, $constraint);

        $this->assertNoViolation();
    }

    public function testNotValidatePropertyNull()
    {
        $variantLink = new ProductVariantLink();

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->validator->validate($variantLink, $constraint);

        $this->assertNoViolation();
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeStringExistingProduct()
    {
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
                self::VARIANT_FIELD_KEY_COLOR => 'Blue'
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'M',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue'
            ]
        ]);

        $this->expectsRepositoryCallsProductVariantLinks($product, $simpleProducts);

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeString()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue'
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'M',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue'
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeStringExistingProduct()
    {
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
                self::VARIANT_FIELD_KEY_COLOR => 'Blue'
            ],
            [
                self::VARIANT_FIELD_KEY_SIZE => 'L',
                self::VARIANT_FIELD_KEY_COLOR => 'Blue'
            ]
        ]);

        $this->expectsRepositoryCallsProductVariantLinks($product, $simpleProducts);

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->uniqueRequiredMessage)
            ->assertRaised();
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeString()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue'
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => 'L',
                    self::VARIANT_FIELD_KEY_COLOR => 'Blue'
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->uniqueRequiredMessage)
            ->assertRaised();
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('m', 'M'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->uniqueRequiredMessage)
            ->assertRaised();
    }

    public function testPropertyAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeSelect()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue')
                ]
            ]
        );
        $variantLink = new ProductVariantLink($product, $this->getProduct(555));

        $constraint = new UniqueProductVariantLinks();
        $constraint->property = 'parentProduct';
        $this->validator->validate($variantLink, $constraint);

        $this->buildViolation($constraint->uniqueRequiredMessage)
            ->assertRaised();
    }

    public function testDoesNotAddViolationIfAllVariantFieldCombinationsAreUniqueTypeBoolean()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => true
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testAddsViolationIfVariantFieldCombinationsAreNotUniqueTypeBoolean()
    {
        $product = $this->prepareProduct(
            [
                self::VARIANT_FIELD_KEY_SIZE,
                self::VARIANT_FIELD_KEY_COLOR,
                self::VARIANT_FIELD_KEY_SLIM_FIT
            ],
            [
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false
                ],
                [
                    self::VARIANT_FIELD_KEY_SIZE => new TestEnumValue('l', 'L'),
                    self::VARIANT_FIELD_KEY_COLOR => new TestEnumValue('blue', 'Blue'),
                    self::VARIANT_FIELD_KEY_SLIM_FIT => false
                ]
            ]
        );

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->uniqueRequiredMessage)
            ->assertRaised();
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
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

    private function getProductVariantLink(int $id, Product $product, Product $parentProduct): ProductVariantLink
    {
        $productVariantLink = new ProductVariantLink();
        ReflectionUtil::setId($productVariantLink, $id);
        $productVariantLink->setProduct($product);
        $productVariantLink->setParentProduct($parentProduct);

        return $productVariantLink;
    }

    private function expectsRepositoryCallsProductVariantLinks(Product $product, array $simpleProducts): void
    {
        $variantLink1 = $this->getProductVariantLink(1, $simpleProducts[0], $product);
        $variantLink2 = $this->getProductVariantLink(2, $simpleProducts[1], $product);

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

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testDoesNothingIfProductVariantHasNoProduct()
    {
        $product = new Product();
        $product->setType(Product::TYPE_CONFIGURABLE);
        $variantLink = new ProductVariantLink($product, null);
        $product->addVariantLink($variantLink);

        $constraint = new UniqueProductVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }
}
