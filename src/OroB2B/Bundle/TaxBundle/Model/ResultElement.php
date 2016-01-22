<?php

namespace OroB2B\Bundle\TaxBundle\Model;

use Brick\Math\BigNumber;

final class ResultElement extends AbstractResult
{
    const INCLUDING_TAX = 'includingTax';
    const EXCLUDING_TAX = 'excludingTax';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';

    /**
     * @param BigNumber $includingTax
     * @param BigNumber $excludingTax
     * @param BigNumber $taxAmount
     * @param BigNumber $adjustment
     *
     * @return ResultElement
     */
    public static function create(
        BigNumber $includingTax,
        BigNumber $excludingTax,
        BigNumber $taxAmount = null,
        BigNumber $adjustment = null
    ) {
        $resultElement = new static;

        $resultElement->offsetSet(self::INCLUDING_TAX, $includingTax);
        $resultElement->offsetSet(self::EXCLUDING_TAX, $excludingTax);
        $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);
        $resultElement->offsetSet(self::ADJUSTMENT, $adjustment);

        return $resultElement;
    }

    /**
     * @param mixed $includingTax
     * @param mixed $excludingTax
     * @param mixed $taxAmount
     * @param mixed $adjustment
     *
     * @return ResultElement
     */
    public static function createFromRaw($includingTax, $excludingTax, $taxAmount = null, $adjustment = null)
    {
        $resultElement = new static;

        $resultElement->offsetSet(self::INCLUDING_TAX, BigNumber::of($includingTax));
        $resultElement->offsetSet(self::EXCLUDING_TAX, BigNumber::of($excludingTax));
        $resultElement->offsetSet(self::TAX_AMOUNT, BigNumber::of($taxAmount));
        $resultElement->offsetSet(self::ADJUSTMENT, BigNumber::of($adjustment));

        return $resultElement;
    }

    /**
     * @return BigNumber
     */
    public function getIncludingTax()
    {
        return $this->getOffset(self::INCLUDING_TAX);
    }

    /**
     * @return BigNumber
     */
    public function getExcludingTax()
    {
        return $this->getOffset(self::EXCLUDING_TAX);
    }

    /**
     * @return BigNumber
     */
    public function getTaxAmount()
    {
        return $this->getOffset(self::TAX_AMOUNT);
    }

    /**
     * @return BigNumber
     */
    public function getAdjustment()
    {
        return $this->getOffset(self::ADJUSTMENT, 0);
    }
}
