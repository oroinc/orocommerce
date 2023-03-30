<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionsCollectionReferencedByProductKitItems;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionsCollectionReferencedByProductKitItemsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductUnitPrecisionsCollectionReferencedByProductKitItemsValidatorTest extends ConstraintValidatorTestCase
{
    private ProductKitsByUnitPrecisionProvider|\PHPUnit\Framework\MockObject\MockObject
        $productKitsByUnitPrecisionProvider;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function createValidator(): ProductUnitPrecisionsCollectionReferencedByProductKitItemsValidator
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager
            ->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));

        $this->productKitsByUnitPrecisionProvider = $this->createMock(ProductKitsByUnitPrecisionProvider::class);

        return new ProductUnitPrecisionsCollectionReferencedByProductKitItemsValidator(
            $this->productKitsByUnitPrecisionProvider
        );
    }

    public function testGetTargets(): void
    {
        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductUnitPrecisionsCollectionReferencedByProductKitItems::class)
        );

        $this->validator->validate(new ProductStub(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, Product::class));

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenNotPersistentCollection(): void
    {
        $product = (new ProductStub())
            ->addUnitPrecision(new ProductUnitPrecision());

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenPersistentCollectionNotDirty(): void
    {
        $collection = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new ProductUnitPrecision()])
        );
        $product = (new ProductStub())
            ->setUnitPrecisions($collection);

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenPersistentCollectionNoDeleted(): void
    {
        $collection = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new ProductUnitPrecision()])
        );
        $collection->takeSnapshot();
        $collection->add(new ProductUnitPrecision());
        $product = (new ProductStub())
            ->setUnitPrecisions($collection);

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNoReferencedKitItems(): void
    {
        $unitPrecision = new ProductUnitPrecision();
        $collection = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([$unitPrecision])
        );
        $collection->takeSnapshot();
        $product = (new ProductStub())
            ->setUnitPrecisions($collection);
        $product->removeUnitPrecision($unitPrecision);

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn([]);

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($product, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenHasReferencedKitItems(): void
    {
        $unit = (new ProductUnit())
            ->setCode('item');
        $unitPrecision = (new ProductUnitPrecision())
            ->setUnit($unit);
        $collection = new PersistentCollection(
            $this->entityManager,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([$unitPrecision])
        );
        $collection->takeSnapshot();
        $product = (new ProductStub())
            ->setUnitPrecisions($collection);
        $product->removeUnitPrecision($unitPrecision);

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn(['PSKU1', 'PSKU2']);

        $constraint = new ProductUnitPrecisionsCollectionReferencedByProductKitItems();
        $this->validator->validate($product, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ product_unit }}', $unit->getCode())
            ->setParameter('{{ product_kits_skus }}', 'PSKU1, PSKU2')
            ->atPath('property.path.unitPrecisions.' . array_search($unitPrecision, $collection->getSnapshot(), true))
            ->setCode(ProductUnitPrecisionsCollectionReferencedByProductKitItems::UNIT_PRECISION_CANNOT_BE_REMOVED)
            ->assertRaised();
    }
}
