<?php

namespace Oro\Bundle\TaxBundle\Model;

final class ResultElement extends AbstractResultElement
{
    const INCLUDING_TAX = 'includingTax';
    const EXCLUDING_TAX = 'excludingTax';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';

    /**
     * @param string $includingTax
     * @param string $excludingTax
     * @param string|int $taxAmount Tax amount value or null if it doesn't calculated
     * @param string|int $adjustment Adjustment value or null if it doesn't calculated
     *
     * @return ResultElement
     */
    public static function create(
        $includingTax,
        $excludingTax,
        $taxAmount = null,
        $adjustment = null
    ) {
        $resultElement = new static;

        $resultElement->offsetSet(self::INCLUDING_TAX, $includingTax);
        $resultElement->offsetSet(self::EXCLUDING_TAX, $excludingTax);
        if (null !== $taxAmount) {
            $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);
        }
        if (null !== $adjustment) {
            $resultElement->offsetSet(self::ADJUSTMENT, $adjustment);
        }

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
        return $this->getOffset(self::TAX_AMOUNT, 0);
    }

    /**
     * @return string
     */
    public function getAdjustment()
    {
        return $this->getOffset(self::ADJUSTMENT, 0);
    }

    /**
     * @param string $adjustment
     * @return string
     */
    public function setAdjustment($adjustment)
    {
        $this->offsetSet(self::ADJUSTMENT, $adjustment);
    }
}
