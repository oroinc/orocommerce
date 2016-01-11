<?php

namespace OroB2B\src\OroB2B\Bundle\TaxBundle\Transformer;

use OroB2B\Bundle\TaxBundle\Entity\TaxItemValue;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\ResultItem;
use OroB2B\Bundle\TaxBundle\Transformer\BaseTaxTransformer;

class TaxItemValueToResultItemTransformer implements BaseTaxTransformer
{
    /**
     * @param TaxItemValue $taxItemValue
     * @return ResultItem
     */
    public function transform($taxItemValue)
    {
        $unit = $this->createResultElement(
            $taxItemValue->getUnitPriceIncludingTax(),
            $taxItemValue->getUnitPriceExcludingTax(),
            $taxItemValue->getUnitPriceTaxAmount(),
            $taxItemValue->getUnitPriceAdjustment()
        );

        $row = $this->createResultElement(
            $taxItemValue->getRowTotalIncludingTax(),
            $taxItemValue->getRowTotalExcludingTax(),
            $taxItemValue->getRowTotalTaxAmount(),
            $taxItemValue->getRowTotalAdjustment()
        );

        return ResultItem::create($unit, $row, $taxItemValue->getAppliedTaxes());
    }

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param int   $adjustment
     * @return ResultElement
     */
    protected function createResultElement($includingTax, $excludingTax, $taxAmount, $adjustment)
    {
        return ResultElement::create($includingTax, $excludingTax, $taxAmount, $adjustment);
    }

    /**
     * {@inheritdoc}
     * @param ResultItem $result
     */
    public function reverseTransform($result)
    {
        $taxItemValue = new TaxItemValue();
        $taxItemValue
            ->setUnitPriceIncludingTax($result->getUnit()->getIncludingTax())
            ->setUnitPriceExcludingTax($result->getUnit()->getExcludingTax())
            ->setUnitPriceTaxAmount($result->getUnit()->getTaxAmount())
            ->setUnitPriceAdjustment($result->getUnit()->getAdjustment())
            ->setRowTotalIncludingTax($result->getRow()->getIncludingTax())
            ->setRowTotalExcludingTax($result->getRow()->getExcludingTax())
            ->setRowTotalTaxAmount($result->getRow()->getTaxAmount())
            ->setRowTotalAdjustment($result->getRow()->getAdjustment());

        foreach ($result->getTaxes() as $applyTax) {
            $taxItemValue->addAppliedTax($applyTax);
        }

        return $taxItemValue;
    }
}
