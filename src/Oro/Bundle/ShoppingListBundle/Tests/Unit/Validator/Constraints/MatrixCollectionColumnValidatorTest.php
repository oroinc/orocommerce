<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn as MatrixCollectionColumnModel;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\MatrixCollectionColumnValidator;

class MatrixCollectionColumnValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var MatrixCollectionColumnValidator
     */
    protected $validator;

    /** @var
     * Constraint|\MatrixCollectionColumn $constraint
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new MatrixCollectionColumn();
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new MatrixCollectionColumnValidator();
        $this->validator->initialize($this->context);
    }

    /**
     *
     */
    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    /**
     *
     */
    public function testValidateEmptyProduct()
    {
        $this->expectEmptyProductViolation();

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->quantity = 1;

        $this->validator->validate($matrixCollectionColumn, $this->constraint);
    }

    /**
     *
     */
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

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm */
        $rootForm = $this->createMock(FormInterface::class);

        $this->context->expects($this->once())
            ->method('getRoot')
            ->willReturn($rootForm);
        $rootForm->expects($this->once())
            ->method('getData')
            ->willReturn($collection);

        $matrixCollectionColumn = new MatrixCollectionColumnModel();
        $matrixCollectionColumn->product = new ProductWithSizeAndColor();
        $matrixCollectionColumn->product->setUnitPrecision($unitPrecision);
        $matrixCollectionColumn->quantity = 1.123;

        $this->validator->validate($matrixCollectionColumn, $this->constraint);
    }

    /**
     *
     */
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

    /**
     * @param $precision
     */
    public function expectWrongPrecisionViolation($precision)
    {
        $violationBuilder = $this
            ->createMock(ConstraintViolationBuilderInterface::class);
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
