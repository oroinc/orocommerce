<?php

namespace OroB2B\src\OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxValue;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Transformer\BaseTaxTransformer;

class TaxValueToResultTransformer implements BaseTaxTransformer
{
    /**
     * @param TaxValue $taxValue
     * @return Result
     */
    public function transform($taxValue)
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

    /**
     * {@inheritdoc}
     * @param Result $result
     */
    public function reverseTransform($result)
    {
        $taxValue = new TaxValue();
        $taxValue
            ->setTotalIncludingTax($result->getTotal()->getIncludingTax())
            ->setTotalExcludingTax($result->getTotal()->getExcludingTax())
            ->setTotalTaxAmount($result->getTotal()->getTaxAmount())
            ->setShippingIncludingTax($result->getShipping()->getIncludingTax())
            ->setShippingExcludingTax($result->getShipping()->getExcludingTax())
            ->setShippingTaxAmount($result->getShipping()->getTaxAmount());

        foreach ($result->getTaxes() as $applyTax) {
            $taxValue->addAppliedTax($applyTax);
        }

        return $taxValue;
    }
}
