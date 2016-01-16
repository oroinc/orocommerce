<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
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
        $taxAmount = 0;
        $taxAdjustment = 0;

        foreach ($taxRules as $taxRule) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRule->getTax()->getRate());
            $taxAmount += $resultElement->getTaxAmount();
            $taxAdjustment += $resultElement->getAdjustment();
        }

        $result->offsetSet(
            Result::UNIT,
            ResultElement::create(
                $this->roundingService->round($taxableAmount),
                $this->roundingService->round($taxableAmount - $taxAmount),
                $this->roundingService->round($taxAmount),
                $this->roundingService->round($taxAdjustment, TaxCalculatorInterface::ADJUSTMENT_SCALE)
            )
        );
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param string $taxableAmount
     */
    protected function resolveRowTotal(Result $result, array $taxRules, $taxableAmount)
    {
        $taxAmount = 0;
        $taxAdjustment = 0;

        $taxResults = [];

        if ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $taxableAmount = (string)$this->roundingService->round($taxableAmount);
        }
        $roundedTaxableAmount = $this->roundingService->round($taxableAmount);

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRule->getTax()->getRate();
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
            $taxAmount += $resultElement->getTaxAmount();
            $taxAdjustment += $resultElement->getAdjustment();

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax() ? $taxRule->getTax()->getId() : null,
                $taxRate,
                $roundedTaxableAmount,
                $resultElement->getTaxAmount()
            );
        }

        $result->offsetSet(
            Result::ROW,
            ResultElement::create(
                $roundedTaxableAmount,
                $this->roundingService->round($taxableAmount - $taxAmount),
                $this->roundingService->round($taxAmount),
                $this->roundingService->round($taxAdjustment, TaxCalculatorInterface::ADJUSTMENT_SCALE)
            )
        );

        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
