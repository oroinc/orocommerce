<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class ResultElementTest extends \PHPUnit_Framework_TestCase
{
    const INCLUDING_TAX = 1.2;
    const EXCLUDING_TAX = 1;
    const TAX_AMOUNT = 2;
    const ADJUSTMENT = 3.4;

    public function testProperties()
    {
        $resultElement = $this->createResultElementModel();
        $this->assertEquals(static::INCLUDING_TAX, $resultElement->getIncludingTax());
        $this->assertEquals(static::EXCLUDING_TAX, $resultElement->getExcludingTax());
        $this->assertEquals(static::TAX_AMOUNT, $resultElement->getTaxAmount());
        $this->assertEquals(static::ADJUSTMENT, $resultElement->getAdjustment());

        $this->assertCount(4, $resultElement);
        $expected = [
            'includingTax' => self::INCLUDING_TAX,
            'excludingTax' => self::EXCLUDING_TAX,
            'taxAmount' => self::TAX_AMOUNT,
            'adjustment' => self::ADJUSTMENT,
        ];

        foreach ($resultElement as $key => $value) {
            $this->assertArrayHasKey($key, $expected);
            $this->assertEquals($expected[$key], $value);
        }
    }

    /**
     * @return ResultElement
     */
    protected function createResultElementModel()
    {
        return ResultElement::create(
            static::INCLUDING_TAX,
            static::EXCLUDING_TAX,
            static::TAX_AMOUNT,
            static::ADJUSTMENT
        );
    }

    public function testConstruct()
    {
        $this->assertEquals(
            $this->createResultElementModel(),
            new ResultElement(
                [
                    ResultElement::EXCLUDING_TAX => static::EXCLUDING_TAX,
                    ResultElement::INCLUDING_TAX => static::INCLUDING_TAX,
                    ResultElement::TAX_AMOUNT => static::TAX_AMOUNT,
                    ResultElement::ADJUSTMENT => static::ADJUSTMENT,
                ]
            )
        );
    }
}
