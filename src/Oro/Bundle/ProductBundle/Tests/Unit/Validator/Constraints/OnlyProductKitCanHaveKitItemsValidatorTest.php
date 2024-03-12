<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\OnlyProductKitCanHaveKitItems;
use Oro\Bundle\ProductBundle\Validator\Constraints\OnlyProductKitCanHaveKitItemsValidator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OnlyProductKitCanHaveKitItemsValidatorTest extends ConstraintValidatorTestCase
{
    private EntityManagerInterface|MockObject $entityManager;

    private UnitOfWork|MockObject $unitOfWork;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        parent::setUp();
    }

    protected function createValidator(): OnlyProductKitCanHaveKitItemsValidator
    {
        return new OnlyProductKitCanHaveKitItemsValidator();
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, OnlyProductKitCanHaveKitItems::class)
        );

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWhenNull(): void
    {
        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($value, $constraint);
    }

    /**
     * @dataProvider productTypeDataProvider
     */
    public function testValidateWhenNotPersistentCollectionAndNoKitItems(string $type): void
    {
        $product = new ProductStub();
        $product->setType($type);

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function productTypeDataProvider(): array
    {
        return [[Product::TYPE_SIMPLE], [Product::TYPE_CONFIGURABLE], [Product::TYPE_KIT]];
    }

    /**
     * @dataProvider notProductKitTypeDataProvider
     */
    public function testValidateWhenNotProductKitAndNotPersistentCollectionAndHasKitItems(string $type): void
    {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType($type)
            ->addKitItem(new ProductKitItemStub());

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ sku }}', '"' . $product->getSku() . '"')
            ->setCause($product)
            ->setInvalidValue($product->getType())
            ->setCode($constraint::MUST_BE_PRODUCT_KIT)
            ->assertRaised();
    }

    public function testValidateWhenNotProductKitAndNotPersistentCollectionAndNoKitItems(): void
    {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType(Product::TYPE_KIT)
            ->addKitItem(new ProductKitItemStub());

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider notProductKitTypeDataProvider
     */
    public function testValidateWhenNotProductKitAndPersistentCollectionAndHasKitItems(string $type): void
    {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType($type);

        $kitItems = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new ProductKitItemStub()])
        );

        ReflectionUtil::setPropertyValue($product, 'kitItems', $kitItems);

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ sku }}', '"' . $product->getSku() . '"')
            ->setCause($product)
            ->setInvalidValue($product->getType())
            ->setCode($constraint::MUST_BE_PRODUCT_KIT)
            ->assertRaised();
    }

    /**
     * @dataProvider notProductKitTypeDataProvider
     */
    public function testValidateWhenNotProductKitAndPersistentCollectionAndNoKitItems(string $type): void
    {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType($type);

        $kitItems = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );

        ReflectionUtil::setPropertyValue($product, 'kitItems', $kitItems);

        $constraint = new OnlyProductKitCanHaveKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider notProductKitTypeDataProvider
     */
    public function testValidateWhenNotProductKitAndPersistentCollectionAndForceInitializeAndHasKitItems(
        string $type
    ): void {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType($type);

        $kitItems = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new ProductKitItemStub()])
        );
        $kitItems->setOwner($product, ['inversedBy' => 'kitItems', 'isOwningSide' => false]);
        $kitItems->setInitialized(false);

        ReflectionUtil::setPropertyValue($product, 'kitItems', $kitItems);

        $this->unitOfWork
            ->expects(self::once())
            ->method('loadCollection')
            ->with($kitItems)
            ->willReturnCallback(function (PersistentCollection $collection) {
                $collection->add(new ProductKitItemStub());
            });

        $constraint = new OnlyProductKitCanHaveKitItems(['forceInitialize' => true]);
        $this->validator->validate($product, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ sku }}', '"' . $product->getSku() . '"')
            ->setCause($product)
            ->setInvalidValue($product->getType())
            ->setCode($constraint::MUST_BE_PRODUCT_KIT)
            ->assertRaised();
    }

    /**
     * @dataProvider notProductKitTypeDataProvider
     */
    public function testValidateWhenNotProductKitAndPersistentCollectionAndForceInitializeAndNoKitItems(
        string $type
    ): void {
        $product = (new ProductStub())
            ->setSku('SKU1')
            ->setType($type);

        $kitItems = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new ProductKitItemStub()])
        );
        $kitItems->setOwner($product, ['inversedBy' => 'kitItems', 'isOwningSide' => false]);
        $kitItems->setInitialized(false);

        ReflectionUtil::setPropertyValue($product, 'kitItems', $kitItems);

        $this->unitOfWork
            ->expects(self::once())
            ->method('loadCollection')
            ->with($kitItems)
            ->willReturnCallback(function (PersistentCollection $collection) {
                // Do nothing.
            });

        $constraint = new OnlyProductKitCanHaveKitItems(['forceInitialize' => true]);
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function notProductKitTypeDataProvider(): array
    {
        return [[Product::TYPE_SIMPLE], [Product::TYPE_CONFIGURABLE]];
    }
}
