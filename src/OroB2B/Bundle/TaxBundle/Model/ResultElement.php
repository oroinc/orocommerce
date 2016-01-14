<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class ResultElement extends AbstractResult
{
    const INCLUDING_TAX = 'includingTax';
    const EXCLUDING_TAX = 'excludingTax';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param float $adjustment
     *
     * @return ResultElement
     */
    public static function create($includingTax, $excludingTax, $taxAmount = 0.00, $adjustment = 0.00)
    {
        $resultElement = new static;

        $resultElement->offsetSet(self::INCLUDING_TAX, $includingTax);
        $resultElement->offsetSet(self::EXCLUDING_TAX, $excludingTax);
        $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);
        $resultElement->offsetSet(self::ADJUSTMENT, $adjustment);

        return $resultElement;
    }

    /**
     * @return float
     */
    public function getIncludingTax()
    {
        return $this->getOffset(self::INCLUDING_TAX);
    }

    /**
     * @return float
     */
    public function getExcludingTax()
    {
        return $this->getOffset(self::EXCLUDING_TAX);
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->getOffset(self::TAX_AMOUNT);
    }

    /**
     * @return float
     */
    public function getAdjustment()
    {
        return $this->getOffset(self::ADJUSTMENT, 0);
    }
}
