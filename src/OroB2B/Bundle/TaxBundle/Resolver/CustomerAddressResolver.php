<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class CustomerAddressResolver extends AbstractAddressResolver
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        $address = $taxable->getDestination();
        $taxRules = $this->matcher->match($address);
        $result = $event->getResult();
        $taxResults = [];

        $isItem = $taxable->getItems()->isEmpty();
        $taxableAmount = $taxable->getAmount();
        $totalTaxAmount = 0;
        foreach ($taxRules as $taxRule) {
            $taxAmount = $this->calculator->calculate($taxableAmount, $taxRule);
            $totalTaxAmount += $taxAmount;

            $tax = $taxRule->getTax();
            $taxResults[] = TaxResultElement::create($tax->getId(), $tax->getRate(), $taxableAmount, $taxAmount);
        }

        $result->offsetSet(Result::TOTAL, $totalTaxAmount);
        $result->offsetSet(Result::TAXES, $taxResults);

        if ($isItem) {
            $priceTaxAmount = 0;
            foreach ($taxRules as $taxRule) {
                $priceTaxAmount += $this->calculator->calculate($taxable->getPrice(), $taxRule);
            }
            $result->offsetSet(Result::UNIT, $priceTaxAmount);
        }
    }
}
