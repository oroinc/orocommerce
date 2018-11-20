<?php

namespace Oro\Bundle\TaxBundle\Model;

abstract class AbstractResultElement extends AbstractResult
{
    const CURRENCY = 'currency';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';

    /**
     * @param string $index
     * @param string $value
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet((string)$index, (string)$value);
    }

    /**
     * @param string $currency
     * @return AbstractResultElement
     */
    public function setCurrency($currency)
    {
        $this->offsetSet(self::CURRENCY, $currency);

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getOffset(self::CURRENCY);
    }

    /**
     * @return string
     */
    public function getTaxAmount()
    {
        return $this->getOffset(self::TAX_AMOUNT, '0');
    }

    /**
     * @param string $adjustment
     * @return self
     */
    public function setAdjustment($adjustment)
    {
        $this->offsetSet(self::ADJUSTMENT, $adjustment);

        return $this;
    }

    /**
     * @return string
     */
    public function getAdjustment()
    {
        return $this->getOffset(self::ADJUSTMENT, '0');
    }
}
