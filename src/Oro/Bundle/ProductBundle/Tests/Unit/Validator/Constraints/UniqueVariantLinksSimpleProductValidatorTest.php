<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinks;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProduct;
use Oro\Bundle\ProductBundle\Validator\Constraints\UniqueVariantLinksSimpleProductValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UniqueVariantLinksSimpleProductValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $uniqueVariantLinksProductValidatorMock;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->uniqueVariantLinksProductValidatorMock = $this->createMock(ValidatorInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        parent::setUp();
    }

    protected function createValidator(): UniqueVariantLinksSimpleProductValidator
    {
        return new UniqueVariantLinksSimpleProductValidator(
            $this->uniqueVariantLinksProductValidatorMock,
            $this->registry
        );
    }

    public function testGetTargets()
    {
        $constraint = new UniqueVariantLinksSimpleProduct();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateUnsupportedClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Entity must be instance of "Oro\Bundle\ProductBundle\Entity\Product", "stdClass" given'
        );

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testSkipIfProductConfigurable()
    {
        $product = (new Product())->setType(Product::TYPE_CONFIGURABLE);

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testSkipIfProductHasNoParentVariantLinks()
    {
        $product = (new Product())->setType(Product::TYPE_SIMPLE);

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutErrors()
    {
        $parentProduct = (new Product())->setType(Product::TYPE_CONFIGURABLE);
        $product = $this->prepareProduct([$parentProduct]);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->once())
            ->method('validate')
            ->with($parentProduct, new UniqueProductVariantLinks())
            ->willReturn(new ConstraintViolationList());

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutErrorsExistingEntity()
    {
        $parentProduct = (new Product())->setType(Product::TYPE_CONFIGURABLE);
        $product = $this->prepareProduct([], 1);

        $this->uniqueVariantLinksProductValidatorMock->expects($this->once())
            ->method('validate')
            ->with($parentProduct, new UniqueProductVariantLinks())
            ->willReturn(new ConstraintViolationList());

        $this->expectsRepositoryCallsProductVariantLinks($product, [$parentProduct]);

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
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
            ->willReturn(new ConstraintViolationList([
                new ConstraintViolation(
                    'message',
                    '',
                    [],
                    '',
                    '',
                    ''
                )
            ]));

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%products%', 'sku1, sku2')
            ->assertRaised();
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
                new ConstraintViolationList([
                    new ConstraintViolation(
                        'message',
                        '',
                        [],
                        '',
                        '',
                        ''
                    )
                ]),
                new ConstraintViolationList()
            );

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%products%', 'sku1')
            ->assertRaised();
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
                new ConstraintViolationList([
                    new ConstraintViolation(
                        'message',
                        '',
                        [],
                        '',
                        '',
                        ''
                    )
                ]),
                new ConstraintViolationList()
            );

        $this->expectsRepositoryCallsProductVariantLinks($product, [$parentProduct, $parentProduct2]);

        $constraint = new UniqueVariantLinksSimpleProduct();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%products%', 'sku1')
            ->assertRaised();
    }

    private function prepareProduct(array $parentProducts = [], int $id = null): Product
    {
        $product = (new StubProduct())
            ->setType(Product::TYPE_SIMPLE)
            ->setId($id);

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

    private function getProductVariantLink(int $id, Product $product, Product $parentProduct): ProductVariantLink
    {
        $productVariantLink = new ProductVariantLink();
        ReflectionUtil::setId($productVariantLink, $id);
        $productVariantLink->setProduct($product);
        $productVariantLink->setParentProduct($parentProduct);

        return $productVariantLink;
    }

    private function expectsRepositoryCallsProductVariantLinks(Product $product, array $parentProducts): void
    {
        $k = 1;
        $variantLinks = [];
        foreach ($parentProducts as $parentProduct) {
            $variantLinks[] = $this->getProductVariantLink($k, $product, $parentProduct);
            $k++;
        }

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->willReturn($variantLinks);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ProductVariantLink::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ProductVariantLink::class)
            ->willReturn($em);
    }
}
