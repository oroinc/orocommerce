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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MatrixCollectionColumnValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var MatrixCollectionColumnValidator
     */
    protected $validator;

    /**
     * @var Constraint|MatrixCollectionColumn
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new MatrixCollectionColumn();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new MatrixCollectionColumnValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateEmptyProduct()
    {
        $this->expectEmptyProductViolation();

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->quantity = 1;

        $this->validator->validate($matrixCollectionColumn, $this->constraint);
    }

    public function testValidateWrongPrecision()
    {
        $validPrecision = 0;
        $this->expectWrongPrecisionViolation($validPrecision);

        $unit = new ProductUnit();
        $unit->setCode('item');
        $collection = new MatrixCollection();
        $collection->unit = $unit;
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setPrecision($validPrecision);
        $unitPrecision->setUnit($unit);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $parentForm */
        $rootForm = $this->createMock(FormInterface::class);

        $this->context->expects($this->once())
            ->method('getRoot')
            ->willReturn($rootForm);
        $rootForm->expects($this->once())
            ->method('getData')
            ->willReturn($collection);

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->product = new Product();
        $matrixCollectionColumn->product->addUnitPrecision($unitPrecision);
        $matrixCollectionColumn->quantity = 1.123;

        $this->validator->validate($matrixCollectionColumn, $this->constraint);
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

        $this->context->expects($this->once())
            ->method('getRoot')
            ->willReturn($rootForm);

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->product = $product;
        $matrixCollectionColumn->quantity = $quantity;

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($matrixCollectionColumn, $this->constraint);
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

    public function expectEmptyProductViolation()
    {
        $violationBuilder = $this
            ->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('quantity')
            ->willReturnSelf();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->messageOnProductUnavailable)
            ->willReturn($violationBuilder);
    }

    public function expectWrongPrecisionViolation($precision)
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('quantity')
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('setParameter')
            ->with('{{ precision }}', $precision)
            ->willReturnSelf();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->messageOnNonValidPrecision)
            ->willReturn($violationBuilder);
    }
}
