<?php

namespace OroB2B\Bundle\TaxBundle\Model;

final class ResultElement
{
    /**
     * @var float
     */
    private $includingTax;

    /**
     * @var float
     */
    private $excludingTax;

    /**
     * @var float
     */
    private $taxAmount;

    /**
     * @var float
     */
    private $adjustment;

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param float $adjustment
     */
    public function __construct($includingTax, $excludingTax, $taxAmount, $adjustment)
    {
        $this->includingTax = $includingTax;
        $this->excludingTax = $excludingTax;
        $this->taxAmount = $taxAmount;
        $this->adjustment = $adjustment;
    }

    /**
     * @return float
     */
    public function getIncludingTax()
    {
        return $this->includingTax;
    }

    /**
     * @return float
     */
    public function getExcludingTax()
    {
        return $this->excludingTax;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * @return float
     */
    public function getAdjustment()
    {
        return $this->adjustment;
    }
}
