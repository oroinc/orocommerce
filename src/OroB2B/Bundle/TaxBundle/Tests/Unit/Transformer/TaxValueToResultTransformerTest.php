<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Transformer;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\src\OroB2B\Bundle\TaxBundle\Transformer\TaxValueToResultTransformer;

class TaxValueToResultTransformerTest extends \PHPUnit_Framework_TestCase
{
    const TOTAL_INCLUDING_TAX = 20;
    const TOTAL_EXCLUDING_TAX = 22;
    const TOTAL_TAX_AMOUNT = 450;

    const SHIPPING_INCLUDING_TAX = 30;
    const SHIPPING_EXCLUDING_TAX = 32;
    const SHIPPING_TAX_AMOUNT = 550;
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
            ->setTotalIncludingTax(static::TOTAL_INCLUDING_TAX)
            ->setTotalExcludingTax(static::TOTAL_EXCLUDING_TAX)
            ->setTotalTaxAmount(static::TOTAL_TAX_AMOUNT)
            ->setShippingIncludingTax(static::SHIPPING_INCLUDING_TAX)
            ->setShippingExcludingTax(static::SHIPPING_EXCLUDING_TAX)
            ->setShippingTaxAmount(static::SHIPPING_TAX_AMOUNT)
            ->addAppliedTax(new TaxApply());

        $result = $this->taxValueToResultTransformer->transform($taxValue);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(static::TOTAL_INCLUDING_TAX, $result->getTotal()->getIncludingTax());
        $this->assertEquals(static::TOTAL_EXCLUDING_TAX, $result->getTotal()->getExcludingTax());
        $this->assertEquals(static::TOTAL_TAX_AMOUNT, $result->getTotal()->getTaxAmount());

        $this->assertEquals(static::SHIPPING_INCLUDING_TAX, $result->getShipping()->getIncludingTax());
        $this->assertEquals(static::SHIPPING_EXCLUDING_TAX, $result->getShipping()->getExcludingTax());
        $this->assertEquals(static::SHIPPING_TAX_AMOUNT, $result->getShipping()->getTaxAmount());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $result->getTaxes());
        $this->assertCount(1, $result->getTaxes());
    }

    public function testReverseTransform()
    {
        $taxResult = $this->createTaxResult();

        $result = $this->taxValueToResultTransformer->reverseTransform($taxResult);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Entity\TaxValue', $result);
        $this->assertEquals(static::TOTAL_INCLUDING_TAX, $result->getTotalIncludingTax());
        $this->assertEquals(static::TOTAL_EXCLUDING_TAX, $result->getTotalExcludingTax());
        $this->assertEquals(static::TOTAL_TAX_AMOUNT, $result->getTotalTaxAmount());

        $this->assertEquals(static::SHIPPING_INCLUDING_TAX, $result->getShippingIncludingTax());
        $this->assertEquals(static::SHIPPING_EXCLUDING_TAX, $result->getShippingExcludingTax());
        $this->assertEquals(static::SHIPPING_TAX_AMOUNT, $result->getShippingTaxAmount());
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
     * @return Result
     */
    protected function createTaxResult()
    {
        $taxResult = Result::create(
            $this->createResultElement(
                static::TOTAL_INCLUDING_TAX,
                static::TOTAL_EXCLUDING_TAX,
                static::TOTAL_TAX_AMOUNT
            ),
            $this->createResultElement(
                static::SHIPPING_INCLUDING_TAX,
                static::SHIPPING_EXCLUDING_TAX,
                static::SHIPPING_TAX_AMOUNT
            ),
            new ArrayCollection([new TaxApply()])
        );

        return $taxResult;
    }
}
