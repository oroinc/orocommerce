<?php

namespace OroB2B\Bundle\TaxBundle\Model;

class TaxResultElement extends AbstractResult
{
    const TAX = 'tax';
    const RATE = 'rate';
    const TAXABLE_AMOUNT = 'taxable_amount';
    const TAX_AMOUNT = 'tax_amount';

    /**
     * @param string $taxId
     * @param string $rate
     * @param string $taxableAmount
     * @param string $taxAmount
     * @return TaxResultElement
     */
    public static function create($taxId, $rate, $taxableAmount, $taxAmount)
    {
        $resultElement = new static;

        $resultElement->offsetSet(self::TAX, $taxId);
        $resultElement->offsetSet(self::RATE, $rate);
        $resultElement->offsetSet(self::TAXABLE_AMOUNT, $taxableAmount);
        $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);

        return $resultElement;
    }
}
