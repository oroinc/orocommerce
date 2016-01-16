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

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableAmount = $taxable->getAmount();
        $roundedTaxableAmount = $this->roundingService->round($taxableAmount);

        $taxAmount = 0;
        $taxResults = [];

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRule->getTax()->getRate();
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
            $taxAmount += $resultElement->getTaxAmount();

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax() ? $taxRule->getTax()->getId() : null,
                $taxRate,
                $roundedTaxableAmount,
                $resultElement->getTaxAmount()
            );
        }

        $result = $event->getResult();
        $result->offsetSet(
            Result::TOTAL,
            ResultElement::create(
                $roundedTaxableAmount,
                $this->roundingService->round($taxableAmount - $taxAmount)
            )
        );
        $result->offsetSet(Result::SHIPPING, new ResultElement());
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
