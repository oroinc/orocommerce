<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn as MatrixCollectionColumnModel;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumnValidator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MatrixCollectionColumnValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): MatrixCollectionColumnValidator
    {
        return new MatrixCollectionColumnValidator();
    }

    public function testGetTargets()
    {
        $constraint = new MatrixCollectionColumn();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateEmptyProduct()
    {
        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->quantity = 1;

        $constraint = new MatrixCollectionColumn();
        $this->validator->validate($matrixCollectionColumn, $constraint);

        $this->buildViolation($constraint->messageOnProductUnavailable)
            ->atPath('property.path.quantity')
            ->assertRaised();
    }

    public function testValidateWrongPrecision()
    {
        $validPrecision = 0;

        $unit = new ProductUnit();
        $unit->setCode('item');
        $collection = new MatrixCollection();
        $collection->unit = $unit;
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setPrecision($validPrecision);
        $unitPrecision->setUnit($unit);

        $rootForm = $this->createMock(FormInterface::class);
        $rootForm->expects($this->once())
            ->method('getData')
            ->willReturn($collection);

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->product = new Product();
        $matrixCollectionColumn->product->addUnitPrecision($unitPrecision);
        $matrixCollectionColumn->quantity = 1.123;

        $this->setRoot($rootForm);
        $constraint = new MatrixCollectionColumn();
        $this->validator->validate($matrixCollectionColumn, $constraint);

        $this->buildViolation($constraint->messageOnNonValidPrecision)
            ->setParameter('{{ precision }}', $validPrecision)
            ->atPath('property.path.quantity')
            ->assertRaised();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidateCorrectPrecision(int $validPrecision, float $quantity): void
    {
        $unit = new ProductUnit();
        $unit->setCode('item');

        $collection = new MatrixCollection();
        $collection->unit = $unit;

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setPrecision($validPrecision);
        $unitPrecision->setUnit($unit);

        $product = new Product();
        $product->addUnitPrecision($unitPrecision);

        $rootForm = $this->createMock(FormInterface::class);
        $rootForm->expects($this->once())
            ->method('getData')
            ->willReturn($collection);

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->product = $product;
        $matrixCollectionColumn->quantity = $quantity;

        $this->setRoot($rootForm);
        $constraint = new MatrixCollectionColumn();
        $this->validator->validate($matrixCollectionColumn, $constraint);

        $this->assertNoViolation();
    }

    public function validateDataProvider(): array
    {
        return [
            [
                'validPrecision' => 3,
                'quantity' => 1.123,
            ],
            [
                'validPrecision' => 2,
                'quantity' => 999.99,
            ],
            [
                'validPrecision' => 10,
                'quantity' => 999.9999999999,
            ],
        ];
    }
}
