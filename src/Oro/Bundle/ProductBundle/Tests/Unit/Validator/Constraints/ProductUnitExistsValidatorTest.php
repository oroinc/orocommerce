<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitExists;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitExistsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductUnitExistsValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new ProductUnitExistsValidator();
    }

    public function testInvalidConstraint()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(null, $this->createMock(Constraint::class));
    }

    private function getUnitPrecision(string $unitCode, bool $isSell = false): ProductUnitPrecision
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $unitPrecision->setSell($isSell);

        return $unitPrecision;
    }

    public function testNullValue()
    {
        $this->validator->validate(null, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testNullProductUnit()
    {
        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn(null);

        $this->validator->validate($value, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testNullProductUnitCode()
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn(null);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        $this->validator->validate($value, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testNullProduct()
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn('item');

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->validator->validate($value, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testUnitExistsForLineItem()
    {
        $unitCode = 'item';

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('item'), $this->getUnitPrecision('set')]);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testUnitDoesNotExistForLineItem()
    {
        $sku = 'SKU1';
        $unitCode = 'item';

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('set')]);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn($sku);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new ProductUnitExists());

        $this->buildViolation('oro.product.productunit.invalid')
            ->setParameter('{{ sku }}', $sku)
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }

    public function testSellUnitExistsForLineItem()
    {
        $unitCode = 'set';

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('item'), $this->getUnitPrecision('set', true)]);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new ProductUnitExists(['sell' => true]));

        $this->assertNoViolation();
    }

    public function testSellUnitDoesNotExistForLineItem()
    {
        $sku = 'SKU1';
        $unitCode = 'item';

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('item'), $this->getUnitPrecision('set', true)]);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn($sku);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new ProductUnitExists(['sell' => true]));

        $this->buildViolation('oro.product.productunit.invalid')
            ->setParameter('{{ sku }}', $sku)
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }

    public function testUnitExistsForQuickAddRow()
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('item'), $this->getUnitPrecision('set')]);

        $value = new QuickAddRow(1, 'SKU1', 3, 'item');
        $value->setProduct($product);

        $this->validator->validate($value, new ProductUnitExists());

        $this->assertNoViolation();
    }

    public function testUnitDoesNotExistForQuickAddRow()
    {
        $sku = 'SKU1';
        $unitCode = 'item';

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn([$this->getUnitPrecision('set')]);
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn($sku);

        $value = new QuickAddRow(1, $sku, 3, $unitCode);
        $value->setProduct($product);

        $this->validator->validate($value, new ProductUnitExists());

        $this->buildViolation('oro.product.productunit.invalid')
            ->setParameter('{{ sku }}', $sku)
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }
}
