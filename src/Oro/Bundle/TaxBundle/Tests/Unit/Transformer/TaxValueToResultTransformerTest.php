<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Transformer;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Transformer\TaxValueToResultTransformer;

class TaxValueToResultTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const TOTAL_INCLUDING_TAX = 20;
    private const TOTAL_EXCLUDING_TAX = 22;
    private const TOTAL_TAX_AMOUNT = 450;
    private const TOTAL_TAX_ADJUSTMENT = 10;

    private const SHIPPING_INCLUDING_TAX = 30;
    private const SHIPPING_EXCLUDING_TAX = 32;
    private const SHIPPING_TAX_AMOUNT = 550;
    private const SHIPPING_TAX_ADJUSTMENT = 10;

    private const UNIT_PRICE_INCLUDING_TAX = 40;
    private const UNIT_PRICE_EXCLUDING_TAX = 42;
    private const UNIT_PRICE_TAX_AMOUNT = 650;
    private const UNIT_PRICE_TAX_ADJUSTMENT = 20;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxValueManager */
    private $taxValueManager;

    /** @var TaxValueToResultTransformer */
    private $taxValueToResultTransformer;

    protected function setUp(): void
    {
        $this->taxValueManager = $this->createMock(TaxValueManager::class);

        $this->taxValueToResultTransformer = new TaxValueToResultTransformer($this->taxValueManager);
    }

    public function testTransform()
    {
        $taxValue = (new TaxValue())
            ->setResult($this->createTaxResult())
            ->setAddress('1st street')
            ->setEntityId(1)
            ->setEntityClass(\stdClass::class);

        $result = $this->taxValueToResultTransformer->transform($taxValue);

        $this->assertResult($result);

        $this->assertIsArray($result->getTaxes());
        $this->assertCount(1, $result->getTaxes());
    }

    public function testReverseTransform()
    {
        $taxValue = new TaxValue();
        $this->taxValueManager->expects($this->once())
            ->method('getTaxValue')
            ->willReturn($taxValue);

        $taxResult = $this->createTaxResult();

        $taxable = new Taxable();
        $taxValue = $this->taxValueToResultTransformer->reverseTransform($taxResult, $taxable);

        $this->assertResult($taxValue->getResult());

        $this->assertIsArray($taxValue->getResult()->getTaxes());
        $this->assertCount(1, $taxValue->getResult()->getTaxes());
    }

    private function assertResult(Result $result)
    {
        $this->assertInstanceOf(Result::class, $result);

        $total = $result->getTotal();
        $this->assertInstanceOf(ResultElement::class, $total);
        $this->assertEquals(self::TOTAL_INCLUDING_TAX, $total->getIncludingTax());
        $this->assertEquals(self::TOTAL_EXCLUDING_TAX, $total->getExcludingTax());
        $this->assertEquals(self::TOTAL_TAX_AMOUNT, $total->getTaxAmount());
        $this->assertEquals(self::TOTAL_TAX_ADJUSTMENT, $total->getAdjustment());

        $shipping = $result->getShipping();
        $this->assertInstanceOf(ResultElement::class, $shipping);
        $this->assertEquals(self::SHIPPING_INCLUDING_TAX, $shipping->getIncludingTax());
        $this->assertEquals(self::SHIPPING_EXCLUDING_TAX, $shipping->getExcludingTax());
        $this->assertEquals(self::SHIPPING_TAX_AMOUNT, $shipping->getTaxAmount());
        $this->assertEquals(self::SHIPPING_TAX_ADJUSTMENT, $shipping->getAdjustment());

        $unit = $result->getUnit();
        $this->assertInstanceOf(ResultElement::class, $unit);
        $this->assertEquals(self::UNIT_PRICE_INCLUDING_TAX, $unit->getIncludingTax());
        $this->assertEquals(self::UNIT_PRICE_EXCLUDING_TAX, $unit->getExcludingTax());
        $this->assertEquals(self::UNIT_PRICE_TAX_AMOUNT, $unit->getTaxAmount());
        $this->assertEquals(self::UNIT_PRICE_TAX_ADJUSTMENT, $unit->getAdjustment());

        $row = $result->getRow();
        $this->assertInstanceOf(ResultElement::class, $row);
        $this->assertEmpty($row->getIncludingTax());
        $this->assertEmpty($row->getExcludingTax());
        $this->assertEmpty($row->getTaxAmount());
        $this->assertEmpty($row->getAdjustment());
    }

    private function createResultElement(
        float $includingTax,
        float $excludingTax,
        float $taxAmount,
        int $adjustment
    ): ResultElement {
        return ResultElement::create($includingTax, $excludingTax, $taxAmount, $adjustment);
    }

    private function createTaxResult(): Result
    {
        return new Result(
            [
                Result::TOTAL => $this->createResultElement(
                    self::TOTAL_INCLUDING_TAX,
                    self::TOTAL_EXCLUDING_TAX,
                    self::TOTAL_TAX_AMOUNT,
                    self::TOTAL_TAX_ADJUSTMENT
                ),
                Result::SHIPPING => $this->createResultElement(
                    self::SHIPPING_INCLUDING_TAX,
                    self::SHIPPING_EXCLUDING_TAX,
                    self::SHIPPING_TAX_AMOUNT,
                    self::SHIPPING_TAX_ADJUSTMENT
                ),
                Result::UNIT => $this->createResultElement(
                    self::UNIT_PRICE_INCLUDING_TAX,
                    self::UNIT_PRICE_EXCLUDING_TAX,
                    self::UNIT_PRICE_TAX_AMOUNT,
                    self::UNIT_PRICE_TAX_ADJUSTMENT
                ),
                Result::TAXES => [TaxResultElement::create('2', '0.07', '10', '0.7')],
            ]
        );
    }
}
