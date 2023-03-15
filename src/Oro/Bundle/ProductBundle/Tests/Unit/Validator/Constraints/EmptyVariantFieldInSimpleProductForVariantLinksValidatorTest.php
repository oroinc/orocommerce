<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\EmptyVariantFieldInSimpleProductForVariantLinksValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmptyVariantFieldInSimpleProductForVariantLinksValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        parent::setUp();
    }

    protected function createValidator(): EmptyVariantFieldInSimpleProductForVariantLinksValidator
    {
        return new EmptyVariantFieldInSimpleProductForVariantLinksValidator(
            PropertyAccess::createPropertyAccessor(),
            $this->registry
        );
    }

    public function testGetTargets()
    {
        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateUnsupportedClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testDoesNothingIfProductConfigurable()
    {
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->setType(Product::TYPE_CONFIGURABLE);

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testDoesNothingIfNewProductHasNoParentVariantLinks()
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testDoesNothingIfExistingProductHasNoParentVariantLinks()
    {
        $product = $this->prepareProduct(1);

        $this->getRequiredAttributesForSimpleProductExpectations($product, []);

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithOneErrorNewProduct()
    {
        $parentProduct1 = $this->prepareParentProduct(1, 'sku1', ['color', 'size']);
        $parentProduct2 = $this->prepareParentProduct(2, 'sku2', ['color']);

        $product = $this->prepareProduct(null, 'M', [$parentProduct1, $parentProduct2]);

        $this->registry->expects($this->never())
            ->method($this->anything());

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['%variantField%' => 'color', '%products%' => 'sku1, sku2'])
            ->assertRaised();
    }

    public function testValidateWithOneErrorExistingProduct()
    {
        $product = $this->prepareProduct(1, 'M');

        $this->getRequiredAttributesForSimpleProductExpectations(
            $product,
            [
                ['id' => 1, 'sku' => 'sku1', 'variantFields' => ['color', 'size']],
                ['id' => 2, 'sku' => 'sku2', 'variantFields' => ['color']]
            ]
        );

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['%variantField%' => 'color', '%products%' => 'sku1, sku2'])
            ->assertRaised();
    }

    public function testValidateWithTwoErrorsExistingProduct()
    {
        $product = $this->prepareProduct(1);

        $this->getRequiredAttributesForSimpleProductExpectations(
            $product,
            [
                ['id' => 1, 'sku' => 'sku1', 'variantFields' => ['color', 'size']],
                ['id' => 2, 'sku' => 'sku2', 'variantFields' => ['color']]
            ]
        );

        $constraint = new EmptyVariantFieldInSimpleProductForVariantLinks();
        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters(['%variantField%' => 'color', '%products%' => 'sku1, sku2'])
            ->buildNextViolation($constraint->message)
            ->setParameters(['%variantField%' => 'size', '%products%' => 'sku1'])
            ->assertRaised();
    }

    private function prepareProduct(int $id = null, string $size = null, array $parentProducts = []): StubProduct
    {
        $product = (new StubProduct())
            ->setType(Product::TYPE_SIMPLE);
        if (null !== $id) {
            $product->setId($id);
        }
        if (null !== $size) {
            $product->setSize($size);
        }

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

    private function getRequiredAttributesForSimpleProductExpectations(Product $product, array $attributeInfo): void
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

    private function prepareParentProduct(int $id, string $sku, array $variantFields): Product
    {
        $product = new StubProduct();
        $product->setType(Product::TYPE_CONFIGURABLE)
            ->setId($id)
            ->setSku($sku)
            ->setVariantFields($variantFields);

        return $product;
    }
}
