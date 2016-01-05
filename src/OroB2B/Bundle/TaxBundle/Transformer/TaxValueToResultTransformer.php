<?php

namespace OroB2B\src\OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class TaxValueToResultTransformer
{
    /**
     * @param TaxValue $taxValue
     * @return Result
     */
    public function transform(TaxValue $taxValue)
    {
        $total = $this->createResultElement(
            $taxValue->getTotalIncludingTax(),
            $taxValue->getTotalExcludingTax(),
            $taxValue->getTotalTaxAmount()
        );

        $shipping = $this->createResultElement(
            $taxValue->getShippingIncludingTax(),
            $taxValue->getShippingExcludingTax(),
            $taxValue->getShippingTaxAmount()
        );

        return new Result($total, $shipping, $taxValue->getAppliedTaxes());
    }

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param int   $adjustment
     * @return ResultElement
     */
    protected function createResultElement($includingTax, $excludingTax, $taxAmount, $adjustment = 0)
    {
        return new ResultElement($includingTax, $excludingTax, $taxAmount, $adjustment);
    }
}
