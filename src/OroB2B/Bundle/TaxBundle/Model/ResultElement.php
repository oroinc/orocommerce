<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class ResultElement extends AbstractResultElement
{
    const INCLUDING_TAX = 'includingTax';
    const EXCLUDING_TAX = 'excludingTax';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';

    /**
     * @param string $includingTax
     * @param string $excludingTax
     * @param string $taxAmount
     * @param string $adjustment
     *
     * @return ResultElement
     */
    public static function create(
        $includingTax,
        $excludingTax,
        $taxAmount,
        $adjustment
    ) {
        $resultElement = new static;

        $resultElement->offsetSet(self::INCLUDING_TAX, (string)$includingTax);
        $resultElement->offsetSet(self::EXCLUDING_TAX, (string)$excludingTax);
        $resultElement->offsetSet(self::TAX_AMOUNT, (string)$taxAmount);
        $resultElement->offsetSet(self::ADJUSTMENT, (string)$adjustment);

        return $resultElement;
    }

    /**
     * @return string
     */
    public function getIncludingTax()
    {
        return $this->getOffset(self::INCLUDING_TAX);
    }

    /**
     * @return string
     */
    public function getExcludingTax()
    {
        return $this->getOffset(self::EXCLUDING_TAX);
    }

    /**
     * @return string
     */
    public function getTaxAmount()
    {
        return $this->getOffset(self::TAX_AMOUNT);
    }

    /**
     * @return string
     */
    public function getAdjustment()
    {
        return $this->getOffset(self::ADJUSTMENT, 0);
    }
}
