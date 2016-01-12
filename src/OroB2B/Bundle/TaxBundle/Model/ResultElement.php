<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class ResultElement extends \ArrayObject
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
    public static function create($includingTax, $excludingTax, $taxAmount, $adjustment)
    {
        $resultElement = new ResultElement();

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

    /**
     * @param string $offset
     * @param null $default
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        if (null !== $default) {
            $this->offsetSet($offset, $default);
        }

        return $default;
    }
}
