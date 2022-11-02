<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Contains tax result
 */
final class ResultElement extends AbstractResultElement implements \JsonSerializable
{
    const INCLUDING_TAX = 'includingTax';
    const EXCLUDING_TAX = 'excludingTax';
    const TAX_AMOUNT = 'taxAmount';
    const ADJUSTMENT = 'adjustment';
    const DISCOUNTS_INCLUDED = 'discountsIncluded';

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
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
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

    public function setDiscountsIncluded(bool $isDiscountsIncluded): self
    {
        $this->offsetSet(self::DISCOUNTS_INCLUDED, $isDiscountsIncluded);

        return $this;
    }

    public function isDiscountsIncluded(): bool
    {
        return (bool) $this->getOffset(self::DISCOUNTS_INCLUDED, false);
    }
}
