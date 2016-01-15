<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class CustomerAddressItemResolver extends AbstractAddressResolver
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        if (!$taxable->getItems()->isEmpty()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableUnitPrice = $taxable->getPrice()->getValue();
        $taxableAmount = $taxableUnitPrice * $taxable->getQuantity();

        $result = $event->getResult();
        $this->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
        $this->resolveRowTotal($result, $taxRules, $taxableAmount);
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param string $taxableAmount
     */
    protected function resolveUnitPrice(Result $result, array $taxRules, $taxableAmount)
    {
        $unitPriceInclTax = 0;
        $unitPriceExclTax = 0;
        $unitPriceTaxAmount = 0;
        $unitPriceAdjustment = 0;

        foreach ($taxRules as $taxRule) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRule);
            $unitPriceInclTax += $resultElement->getIncludingTax();
            $unitPriceExclTax += $resultElement->getExcludingTax();
            $unitPriceTaxAmount += $resultElement->getTaxAmount();
            $unitPriceAdjustment += $resultElement->getAdjustment();
        }

        $result->offsetSet(
            Result::UNIT,
            ResultElement::create($unitPriceInclTax, $unitPriceExclTax, $unitPriceTaxAmount, $unitPriceAdjustment)
        );
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param string $taxableAmount
     */
    protected function resolveRowTotal(Result $result, array $taxRules, $taxableAmount)
    {
        $rowTotalInclTax = 0;
        $rowTotalExclTax = 0;
        $rowTotalTaxAmount = 0;
        $rowTotalAdjustment = 0;

        $taxResults = [];

        if ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $taxableAmount = (string)$this->roundingService->round($taxableAmount);
        }

        foreach ($taxRules as $taxRule) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRule);
            $rowTotalInclTax += $resultElement->getIncludingTax();
            $rowTotalExclTax += $resultElement->getExcludingTax();
            $rowTotalTaxAmount += $resultElement->getTaxAmount();
            $rowTotalAdjustment += $resultElement->getAdjustment();

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax() ? $taxRule->getTax()->getId() : null,
                $taxRule->getTax()->getRate(),
                $taxableAmount,
                $resultElement->getTaxAmount()
            );
        }

        $result->offsetSet(
            Result::ROW,
            ResultElement::create($rowTotalInclTax, $rowTotalExclTax, $rowTotalTaxAmount, $rowTotalAdjustment)
        );

        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
