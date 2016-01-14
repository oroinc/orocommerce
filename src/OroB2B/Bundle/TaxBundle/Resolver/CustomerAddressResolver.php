<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class CustomerAddressResolver extends AbstractAddressResolver
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        if ($taxable->getItems()->isEmpty()) {
            return;
        }

        $address = $this->getAddress($taxable);
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableAmount = $taxable->getAmount();

        $totalInclTax = 0;
        $totalExclTax = 0;
        $taxResults = [];

        foreach ($taxRules as $taxRule) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRule);
            $totalInclTax += $resultElement->getIncludingTax();
            $totalExclTax += $resultElement->getExcludingTax();

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax()->getId(),
                $taxRule->getTax()->getRate(),
                $taxableAmount,
                $resultElement->getTaxAmount()
            );
        }

        $result = $event->getResult();
        $result->offsetSet(Result::TOTAL, ResultElement::create($totalInclTax, $totalExclTax));
        $result->offsetSet(Result::SHIPPING, new ResultElement());
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
