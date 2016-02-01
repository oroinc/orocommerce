<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class TotalResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        /** @var TaxResultElement[] $taxResults */
        $taxResults = [];
        $inclTax = BigDecimal::zero();
        $exclTax = BigDecimal::zero();
        $taxAmount = BigDecimal::zero();
        $adjustment = BigDecimal::zero();

        foreach ($taxable->getItems() as $taxableItem) {
            $taxableItemResult = $taxableItem->getResult();
            $row = $taxableItemResult->getRow();
            try {
                $inclTax = $inclTax->plus($row->getIncludingTax());
                $exclTax = $exclTax->plus($row->getExcludingTax());
                $taxAmount = $taxAmount->plus($row->getTaxAmount());
                $adjustment = $adjustment->plus($row->getAdjustment());
            } catch (NumberFormatException $e) {
                continue;
            }

            foreach ($taxableItemResult->getTaxes() as $appliedTax) {
                $taxCode = (string)$appliedTax->getTax();
                $appliedTaxAmount = $appliedTax->getTaxAmount();
                $appliedTaxableAmount = $appliedTax->getTaxableAmount();
                if (array_key_exists($taxCode, $taxResults)) {
                    $appliedTaxes = $taxResults[$taxCode];
                    $appliedTaxAmount = BigDecimal::of($appliedTaxes->getTaxAmount())->plus($appliedTaxAmount);
                    $appliedTaxableAmount = BigDecimal::of($appliedTaxes->getTaxableAmount())
                        ->plus($appliedTaxableAmount);
                }

                $taxResults[$taxCode] = TaxResultElement::create(
                    $taxCode,
                    $appliedTax->getRate(),
                    $appliedTaxableAmount,
                    $appliedTaxAmount
                );
            }
        }

        $result = $taxable->getResult();
        $result->offsetSet(Result::TOTAL, ResultElement::create($inclTax, $exclTax, $taxAmount, $adjustment));
        $result->offsetSet(Result::TAXES, array_values($taxResults));
    }
}
