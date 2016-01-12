<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Transformer;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Transformer\TaxValueToResultTransformer;

class TaxValueToResultTransformerTest extends \PHPUnit_Framework_TestCase
{
    const TOTAL_INCLUDING_TAX = 20;
    const TOTAL_EXCLUDING_TAX = 22;
    const TOTAL_TAX_AMOUNT = 450;
    const TOTAL_TAX_ADJUSTMENT = 10;

    const SHIPPING_INCLUDING_TAX = 30;
    const SHIPPING_EXCLUDING_TAX = 32;
    const SHIPPING_TAX_AMOUNT = 550;
    const SHIPPING_TAX_ADJUSTMENT = 10;

    const UNIT_PRICE_INCLUDING_TAX = 40;
    const UNIT_PRICE_EXCLUDING_TAX = 42;
    const UNIT_PRICE_TAX_AMOUNT = 650;
    const UNIT_PRICE_TAX_ADJUSTMENT = 20;

    const ROW_PRICE_INCLUDING_TAX = 40;
    const ROW_PRICE_EXCLUDING_TAX = 42;
    const ROW_PRICE_TAX_AMOUNT = 650;
    const ROW_PRICE_TAX_ADJUSTMENT = 20;
    /**
     * @var TaxValueToResultTransformer
     */
    protected $taxValueToResultTransformer;

    public function setUp()
    {
        $this->taxValueToResultTransformer = new TaxValueToResultTransformer();
    }

    public function testTransform()
    {
        $taxValue = (new TaxValue())
            ->setResult($this->createTaxResult())
            ->setAddress('1st street')
            ->setEntityId(1)
            ->setEntityClass('\stdClass')
            ->addAppliedTax(new TaxApply());

        $result = $this->taxValueToResultTransformer->transform($taxValue);

        $this->assertResult($result);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $result->getTaxes());
        $this->assertCount(1, $result->getTaxes());
    }

    public function testReverseTransform()
    {
        $taxResult = $this->createTaxResult();

        $taxValue = $this->taxValueToResultTransformer->reverseTransform($taxResult);

        $this->assertResult($taxValue->getResult());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $taxValue->getResult()->getTaxes());
        $this->assertCount(0, $taxValue->getResult()->getTaxes());
    }

    /**
     * @param Result $result
     */
    protected function assertResult(Result $result)
    {
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);

        $total = $result->getTotal();
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $total);
        $this->assertEquals(static::TOTAL_INCLUDING_TAX, $total->getIncludingTax());
        $this->assertEquals(static::TOTAL_EXCLUDING_TAX, $total->getExcludingTax());
        $this->assertEquals(static::TOTAL_TAX_AMOUNT, $total->getTaxAmount());
        $this->assertEquals(static::TOTAL_TAX_ADJUSTMENT, $total->getAdjustment());

        $shipping = $result->getShipping();
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $shipping);
        $this->assertEquals(static::SHIPPING_INCLUDING_TAX, $shipping->getIncludingTax());
        $this->assertEquals(static::SHIPPING_EXCLUDING_TAX, $shipping->getExcludingTax());
        $this->assertEquals(static::SHIPPING_TAX_AMOUNT, $shipping->getTaxAmount());
        $this->assertEquals(static::SHIPPING_TAX_ADJUSTMENT, $shipping->getAdjustment());

        $unit = $result->getUnit();
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $unit);
        $this->assertEquals(static::UNIT_PRICE_INCLUDING_TAX, $unit->getIncludingTax());
        $this->assertEquals(static::UNIT_PRICE_EXCLUDING_TAX, $unit->getExcludingTax());
        $this->assertEquals(static::UNIT_PRICE_TAX_AMOUNT, $unit->getTaxAmount());
        $this->assertEquals(static::UNIT_PRICE_TAX_ADJUSTMENT, $unit->getAdjustment());

        $this->assertNull($result->getRow());
    }

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param int $adjustment
     * @return ResultElement
     */
    protected function createResultElement($includingTax, $excludingTax, $taxAmount, $adjustment)
    {
        return ResultElement::create($includingTax, $excludingTax, $taxAmount, $adjustment);
    }

    /**
     * @return Result
     */
    protected function createTaxResult()
    {
        $taxResult = new Result(
            [
                Result::TOTAL => $this->createResultElement(
                    static::TOTAL_INCLUDING_TAX,
                    static::TOTAL_EXCLUDING_TAX,
                    static::TOTAL_TAX_AMOUNT,
                    static::TOTAL_TAX_ADJUSTMENT
                ),
                Result::SHIPPING => $this->createResultElement(
                    static::SHIPPING_INCLUDING_TAX,
                    static::SHIPPING_EXCLUDING_TAX,
                    static::SHIPPING_TAX_AMOUNT,
                    static::SHIPPING_TAX_ADJUSTMENT
                ),
                Result::UNIT => $this->createResultElement(
                    static::UNIT_PRICE_INCLUDING_TAX,
                    static::UNIT_PRICE_EXCLUDING_TAX,
                    static::UNIT_PRICE_TAX_AMOUNT,
                    static::UNIT_PRICE_TAX_ADJUSTMENT
                ),
                Result::UNIT => $this->createResultElement(
                    static::ROW_PRICE_INCLUDING_TAX,
                    static::ROW_PRICE_EXCLUDING_TAX,
                    static::ROW_PRICE_TAX_AMOUNT,
                    static::ROW_PRICE_TAX_ADJUSTMENT
                ),
                Result::TAXES => new ArrayCollection([new TaxApply()]),
            ]
        );

        return $taxResult;
    }
}
