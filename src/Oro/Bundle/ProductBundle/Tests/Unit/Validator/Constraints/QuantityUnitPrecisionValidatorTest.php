<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuantityUnitPrecisionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    protected function setUp(): void
    {
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity, $precision) {
                return round($quantity, $precision);
            });

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator(): QuantityUnitPrecisionValidator
    {
        return new QuantityUnitPrecisionValidator($this->roundingService);
    }

    public function testInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(null, $this->createMock(Constraint::class));
    }

    public function testNullValue(): void
    {
        $this->validator->validate(null, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullQuantity(): void
    {
        $quantity = null;

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNotNumberQuantity(): void
    {
        $quantity = 'test';

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullProductUnit(): void
    {
        $quantity = 2.345;

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn(null);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullProductUnitCode(): void
    {
        $quantity = 2.345;

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn(null);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullProduct(): void
    {
        $quantity = 2.345;

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn('item');

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullUnitPrecisionAndNullDefaultPrecision(): void
    {
        $unitCode = 'item';
        $quantity = 2.345;

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn('item');
        $productUnit->expects($this->once())
            ->method('getDefaultPrecision')
            ->willReturn(null);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn(null);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->exactly(2))
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testNullUnitPrecisionAndNotNullDefaultPrecision(): void
    {
        $unitCode = 'item';
        $precision = 2;
        $quantity = 2.345;

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn('item');
        $productUnit->expects($this->once())
            ->method('getDefaultPrecision')
            ->willReturn($precision);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn(null);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->exactly(2))
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->buildViolation('oro.product.productlineitem.quantity.invalid_precision')
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }

    public function testNullPrecision(): void
    {
        $unitCode = 'item';
        $quantity = 2.345;

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->willReturn(null);

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn('item');

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn($unitPrecision);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validPrecisionProvider
     */
    public function testValidPrecisionForLineItem(int $precision, float $quantity): void
    {
        $unitCode = 'item';

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->willReturn($precision);

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn($unitPrecision);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testInvalidPrecisionForLineItem(): void
    {
        $unitCode = 'item';
        $precision = 2;
        $quantity = 2.345;

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->willReturn($precision);

        $productUnit = $this->createMock(ProductUnit::class);
        $productUnit->expects($this->once())
            ->method('getCode')
            ->willReturn($unitCode);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn($unitPrecision);

        $value = $this->createMock(ProductLineItemInterface::class);
        $value->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $value->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $value->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->buildViolation('oro.product.productlineitem.quantity.invalid_precision')
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }

    /**
     * @dataProvider validPrecisionProvider
     */
    public function testValidPrecisionForQuickAddRow(int $precision, float $quantity): void
    {
        $unitCode = 'item';

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->willReturn($precision);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn($unitPrecision);

        $value = new QuickAddRow(1, 'SKU1', $quantity, $unitCode);
        $value->setProduct($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->assertNoViolation();
    }

    public function testInvalidPrecisionForQuickAddRow(): void
    {
        $unitCode = 'item';
        $precision = 2;
        $quantity = 2.345;

        $unitPrecision = $this->createMock(ProductUnitPrecision::class);
        $unitPrecision->expects($this->once())
            ->method('getPrecision')
            ->willReturn($precision);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getUnitPrecision')
            ->with($unitCode)
            ->willReturn($unitPrecision);

        $value = new QuickAddRow(1, 'SKU1', $quantity, $unitCode);
        $value->setProduct($product);

        $this->validator->validate($value, new QuantityUnitPrecision());

        $this->buildViolation('oro.product.productlineitem.quantity.invalid_precision')
            ->setParameter('{{ unit }}', $unitCode)
            ->assertRaised();
    }

    public function validPrecisionProvider(): array
    {
        return [
            'precision 0'     => [
                'precision' => 0,
                'quantity'  => 5
            ],
            'precision 2'     => [
                'precision' => 2,
                'quantity'  => 5.55
            ],
        ];
    }
}
