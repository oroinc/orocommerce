<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionConstraint;
use Oro\Bundle\ProductBundle\Validator\ProductUnitPrecisionValidator;

use Symfony\Component\Validator\ExecutionContextInterface;

class ProductUnitPrecisionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductUnitPrecisionValidator */
    private $validator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var ProductUnitPrecisionConstraint */
    private $constraint;

    protected function setUp()
    {
        $this->validator = new ProductUnitPrecisionValidator();
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new ProductUnitPrecisionConstraint();
        $this->validator->initialize($this->contextMock);
    }

    public function testValidateViolation()
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $productUnitPrecisions[] = (new ProductUnitPrecision())->setUnit($productUnit);
        $productUnitPrecisions[] = (new ProductUnitPrecision())->setUnit($productUnit);
        $this->contextMock->expects($this->once())
            ->method('addViolation')
            ->with('oro.product.unit_precision.duplicate_units');
        $this->validator->validate($productUnitPrecisions, $this->constraint);
    }

    public function testValidateNoViolation()
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $otherUnit = (new ProductUnit())->setCode('item');
        $productUnitPrecisions[] = (new ProductUnitPrecision())->setUnit($productUnit);
        $productUnitPrecisions[] = (new ProductUnitPrecision())->setUnit($otherUnit);
        $this->contextMock->expects($this->never())->method('addViolation');
        $this->validator->validate($productUnitPrecisions, $this->constraint);
    }
}
