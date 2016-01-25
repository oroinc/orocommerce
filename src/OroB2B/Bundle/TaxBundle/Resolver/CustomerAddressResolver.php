<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

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
        if (0 === $taxable->getItems()->count()) {
            return;
        }

        if (null === $taxable->getAmount()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableAmount = $taxable->getAmount();

        $taxResults = [];
        $taxRate = BigDecimal::zero();

        foreach ($taxRules as $taxRule) {
            $currentTaxRate = $taxRule->getTax()->getRate();
            $resultElement = $this->calculator->calculate($taxableAmount, $currentTaxRate);
            $taxRate = $taxRate->plus($currentTaxRate);

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax() ? $taxRule->getTax()->getId() : null,
                $currentTaxRate,
                $resultElement->getExcludingTax(),
                $resultElement->getTaxAmount()
            );
        }

        $result = $event->getResult();
        $result->offsetSet(Result::TOTAL, $this->calculator->calculate($taxableAmount, $taxRate));
        $result->offsetSet(Result::SHIPPING, new ResultElement());
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
