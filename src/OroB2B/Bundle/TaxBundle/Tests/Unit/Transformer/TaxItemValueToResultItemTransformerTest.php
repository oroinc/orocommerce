<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxItemValue;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\ResultItem;
use OroB2B\Bundle\TaxBundle\Transformer\TaxItemValueToResultItemTransformer;

class TaxItemValueToResultItemTransformerTest extends \PHPUnit_Framework_TestCase
{
    const ROW_INCLUDING_TAX = 20;
    const ROW_EXCLUDING_TAX = 22;
    const ROW_TAX_AMOUNT = 450;
    const ROW_ADJUSTMENT = 0.2;

    const UNIT_INCLUDING_TAX = 30;
    const UNIT_EXCLUDING_TAX = 32;
    const UNIT_TAX_AMOUNT = 550;
    const UNIT_ADJUSTMENT = 0.3;

    /**
     * @var TaxItemValueToResultItemTransformer
     */
    protected $taxItemValueToResultItemTransformer;

    public function setUp()
    {
        $this->taxItemValueToResultItemTransformer = new TaxItemValueToResultItemTransformer();
    }

    public function testTransform()
    {
        $taxItemValue = (new TaxItemValue())
            ->setRowTotalIncludingTax(static::ROW_INCLUDING_TAX)
            ->setRowTotalExcludingTax(static::ROW_EXCLUDING_TAX)
            ->setRowTotalTaxAmount(static::ROW_TAX_AMOUNT)
            ->setRowTotalAdjustment(static::ROW_ADJUSTMENT)
            ->setUnitPriceIncludingTax(static::UNIT_INCLUDING_TAX)
            ->setUnitPriceExcludingTax(static::UNIT_EXCLUDING_TAX)
            ->setUnitPriceTaxAmount(static::UNIT_TAX_AMOUNT)
            ->setUnitPriceAdjustment(static::UNIT_ADJUSTMENT)
            ->addAppliedTax(new TaxApply());

        $result = $this->taxItemValueToResultItemTransformer->transform($taxItemValue);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultItem', $result);
        $this->assertEquals(static::ROW_INCLUDING_TAX, $result->getRow()->getIncludingTax());
        $this->assertEquals(static::ROW_EXCLUDING_TAX, $result->getRow()->getExcludingTax());
        $this->assertEquals(static::ROW_TAX_AMOUNT, $result->getRow()->getTaxAmount());
        $this->assertEquals(static::ROW_ADJUSTMENT, $result->getRow()->getAdjustment());

        $this->assertEquals(static::UNIT_INCLUDING_TAX, $result->getUnit()->getIncludingTax());
        $this->assertEquals(static::UNIT_EXCLUDING_TAX, $result->getUnit()->getExcludingTax());
        $this->assertEquals(static::UNIT_TAX_AMOUNT, $result->getUnit()->getTaxAmount());
        $this->assertEquals(static::UNIT_ADJUSTMENT, $result->getUnit()->getAdjustment());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $result->getTaxes());
        $this->assertCount(1, $result->getTaxes());
    }

    public function testReverseTransform()
    {
        $taxResult = $this->createTaxItemResult();

        $result = $this->taxItemValueToResultItemTransformer->reverseTransform($taxResult);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Entity\TaxItemValue', $result);
        $this->assertEquals(static::UNIT_INCLUDING_TAX, $result->getUnitPriceIncludingTax());
        $this->assertEquals(static::UNIT_EXCLUDING_TAX, $result->getUnitPriceExcludingTax());
        $this->assertEquals(static::UNIT_TAX_AMOUNT, $result->getUnitPriceTaxAmount());
        $this->assertEquals(static::UNIT_ADJUSTMENT, $result->getUnitPriceAdjustment());

        $this->assertEquals(static::ROW_INCLUDING_TAX, $result->getRowTotalIncludingTax());
        $this->assertEquals(static::ROW_EXCLUDING_TAX, $result->getRowTotalExcludingTax());
        $this->assertEquals(static::ROW_TAX_AMOUNT, $result->getRowTotalTaxAmount());
        $this->assertEquals(static::ROW_ADJUSTMENT, $result->getRowTotalAdjustment());
    }

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param int   $adjustment
     * @return ResultElement
     */
    protected function createResultElement($includingTax, $excludingTax, $taxAmount, $adjustment = 0)
    {
        return ResultElement::create($includingTax, $excludingTax, $taxAmount, $adjustment);
    }

    /**
     * @return ResultItem
     */
    protected function createTaxItemResult()
    {
        $taxResultItem = ResultItem::create(
            $this->createResultElement(
                static::UNIT_INCLUDING_TAX,
                static::UNIT_EXCLUDING_TAX,
                static::UNIT_TAX_AMOUNT,
                static::UNIT_ADJUSTMENT
            ),
            $this->createResultElement(
                static::ROW_INCLUDING_TAX,
                static::ROW_EXCLUDING_TAX,
                static::ROW_TAX_AMOUNT,
                static::ROW_ADJUSTMENT
            ),
            new ArrayCollection([new TaxApply()])
        );

        return $taxResultItem;
    }
}
