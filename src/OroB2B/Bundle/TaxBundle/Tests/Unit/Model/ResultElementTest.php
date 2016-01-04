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
    }

    /**
     * @return ResultElement
     */
    protected function createResultElementModel()
    {
        return new ResultElement(static::INCLUDING_TAX, static::EXCLUDING_TAX, static::TAX_AMOUNT, static::ADJUSTMENT);
    }
}
