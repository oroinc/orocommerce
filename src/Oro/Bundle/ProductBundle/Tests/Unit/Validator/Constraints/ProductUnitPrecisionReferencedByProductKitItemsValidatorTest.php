<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionReferencedByProductKitItems;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionReferencedByProductKitItemsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductUnitPrecisionReferencedByProductKitItemsValidatorTest extends ConstraintValidatorTestCase
{
    private ProductKitsByUnitPrecisionProvider|\PHPUnit\Framework\MockObject\MockObject
        $productKitsByUnitPrecisionProvider;

    private UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $unitOfWork;

    protected function createValidator(): ProductUnitPrecisionReferencedByProductKitItemsValidator
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $entityManager
            ->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductUnitPrecision::class)
            ->willReturn($entityManager);

        $this->productKitsByUnitPrecisionProvider = $this->createMock(ProductKitsByUnitPrecisionProvider::class);

        return new ProductUnitPrecisionReferencedByProductKitItemsValidator(
            $managerRegistry,
            $this->productKitsByUnitPrecisionProvider
        );
    }

    public function testGetTargets(): void
    {
        $constraint = new ProductUnitPrecisionReferencedByProductKitItems();

        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testValidateUnsupportedConstraint(): void
    {
        $constraint = new IsNull();

        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductUnitPrecisionReferencedByProductKitItems::class)
        );

        $this->validator->validate(new ProductUnitPrecision(), $constraint);
    }

    public function testValidateUnsupportedClass(): void
    {
        $value = new \stdClass();

        $this->expectExceptionObject(new UnexpectedValueException($value, ProductUnitPrecision::class));

        $constraint = new ProductUnitPrecisionReferencedByProductKitItems();
        $this->validator->validate($value, $constraint);
    }

    /**
     * @dataProvider validateWhenNotChangedDataProvider
     */
    public function testValidateWhenNotChanged(array $originalData): void
    {
        $unitPrecision = (new ProductUnitPrecision())
            ->setUnit((new ProductUnit())->setCode('item'));

        $this->unitOfWork
            ->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($unitPrecision)
            ->willReturn($originalData);

        $constraint = new ProductUnitPrecisionReferencedByProductKitItems();
        $this->validator->validate($unitPrecision, $constraint);

        $this->assertNoViolation();
    }

    public function validateWhenNotChangedDataProvider(): array
    {
        return [
            ['originalData' => []],
            ['originalData' => ['unit' => (new ProductUnit())->setCode('item')]],
        ];
    }

    public function testValidateWhenNoReferencedKitItems(): void
    {
        $unitPrecision = (new ProductUnitPrecision())
            ->setUnit((new ProductUnit())->setCode('item'));

        $originalData = ['unit' => (new ProductUnit())->setCode('each')];
        $this->unitOfWork
            ->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($unitPrecision)
            ->willReturn($originalData);

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn([]);

        $constraint = new ProductUnitPrecisionReferencedByProductKitItems();
        $this->validator->validate($unitPrecision, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenHasReferencedKitItems(): void
    {
        $unitPrecision = (new ProductUnitPrecision())
            ->setUnit((new ProductUnit())->setCode('item'));

        $originalData = ['unit' => (new ProductUnit())->setCode('each')];
        $this->unitOfWork
            ->expects(self::once())
            ->method('getOriginalEntityData')
            ->with($unitPrecision)
            ->willReturn($originalData);

        $this->productKitsByUnitPrecisionProvider
            ->expects(self::once())
            ->method('getRelatedProductKitsSku')
            ->with($unitPrecision)
            ->willReturn(['PSKU1', 'PSKU2']);

        $constraint = new ProductUnitPrecisionReferencedByProductKitItems();
        $this->validator->validate($unitPrecision, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ product_unit }}', $unitPrecision->getProductUnitCode())
            ->setParameter('{{ product_kits_skus }}', 'PSKU1, PSKU2')
            ->atPath('property.path.unit')
            ->setCode(ProductUnitPrecisionReferencedByProductKitItems::UNIT_PRECISION_CANNOT_BE_CHANGED)
            ->assertRaised();
    }
}
