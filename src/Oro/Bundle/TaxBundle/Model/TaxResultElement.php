<?php

namespace Oro\Bundle\TaxBundle\Model;

use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration;

/**
 * DTO model class to collect tax result data.
 */
class TaxResultElement extends AbstractResultElement
{
    const TAX = 'tax';
    const RATE = 'rate';
    const TAXABLE_AMOUNT = 'taxableAmount';

    /**
     * @param string $taxCode
     * @param string $rate
     * @param string $taxableAmount
     * @param string $taxAmount
     * @param string $adjustment
     * @return TaxResultElement
     */
    public static function create($taxCode, $rate, $taxableAmount, $taxAmount, $adjustment = null)
    {
        $resultElement = new static;

        $resultElement->offsetSet(self::TAX, $taxCode);
        $resultElement->offsetSet(self::RATE, $rate);
        $resultElement->offsetSet(self::TAXABLE_AMOUNT, $taxableAmount);
        $resultElement->offsetSet(self::TAX_AMOUNT, $taxAmount);
        if ($adjustment) {
            $resultElement->offsetSet(self::ADJUSTMENT, $adjustment);
        }

        /** todo: remove after BB-1752 or BB-2113 */
        $resultElement->offsetSet(self::CURRENCY, Configuration::DEFAULT_CURRENCY);

        return $resultElement;
    }

    /**
     * @return string
     */
    public function getTax()
    {
        return $this->getOffset(self::TAX);
    }

    /**
     * @return string
     */
    public function getRate()
    {
        return $this->getOffset(self::RATE);
    }

    /**
     * @return string
     */
    public function getTaxableAmount()
    {
        return $this->getOffset(self::TAXABLE_AMOUNT);
    }
}
