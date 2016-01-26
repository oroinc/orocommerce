<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class TotalResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        if (!$taxable->getItems()->count()) {
            return;
        }

        /** @var TaxResultElement[] $taxResults */
        $taxResults = [];
        $exclTax = BigDecimal::zero();
        $inclTax = BigDecimal::zero();

        foreach ($taxable->getItems() as $taxableItem) {
            $taxableItemResult = $taxableItem->getResult();
            $row = $taxableItemResult->getRow();
            try {
                $inclTax = $inclTax->plus($row->getIncludingTax());
                $exclTax = $exclTax->plus($row->getExcludingTax());
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
        $result->offsetSet(Result::TOTAL, ResultElement::create($inclTax, $exclTax));
        $result->offsetSet(Result::TAXES, array_values($taxResults));
    }
}
