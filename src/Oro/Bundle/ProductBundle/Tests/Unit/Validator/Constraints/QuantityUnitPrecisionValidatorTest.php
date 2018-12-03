<?php

namespace Oro\Bundle\ProductBundle\Tests\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuantityUnitPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->constraint = new QuantityUnitPrecision();

        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @param $precision
     * @param $quantity
     *
     * @dataProvider validPrecisionProvider
     */
    public function testValidPrecisionForUnit($precision, $quantity)
    {
        $sku = 'SKU1';
        $unit = 'item';
        $quickAddRow = new QuickAddRow(1, $sku, $quantity, $unit);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->method('getPrecision')
            ->willReturn($precision);

        $product = $this->createMock(Product::class);
        $product->method('getUnitPrecision')
            ->with($unit)
            ->willReturn($unitPrecision);

        $quickAddRow->setProduct($product);

        $this->validator->validate($quickAddRow, $this->constraint);

        $this->assertNoViolation();
    }

    public function testInvalidPrecisionForUnit()
    {
        $sku = 'SKU1';
        $unit = 'item';
        $precision = 2;
        $quickAddRow = new QuickAddRow(1, $sku, 2.345, $unit);

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->method('getPrecision')
            ->willReturn($precision);

        $product = $this->createMock(Product::class);
        $product->method('getUnitPrecision')
            ->with($unit)
            ->willReturn($unitPrecision);

        $quickAddRow->setProduct($product);

        $this->validator->validate($quickAddRow, $this->constraint);

        $this->buildViolation('oro.product.frontend.quick_add.validation.invalid_precision')
            ->setParameter('{{ unit }}', 'item')
            ->assertRaised();
    }

    /**
     * @return array
     */
    public function validPrecisionProvider()
    {
        return [
            'precision 0' => [
                'precision' => 0,
                'quantity' => 5
            ],
            'precision 2' => [
                'precision' => 2,
                'quantity' => 5.55
            ]
        ];
    }

    /**
     * @return QuantityUnitPrecisionValidator
     */
    protected function createValidator()
    {
        return new QuantityUnitPrecisionValidator();
    }
}
