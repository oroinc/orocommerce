<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class CustomerAddressItemResolver extends AbstractAddressResolver
{
    /** {@inheritdoc} */
    public function resolve(ResolveTaxEvent $event)
    {
        $taxable = $event->getTaxable();
        if (0 !== $taxable->getItems()->count()) {
            return;
        }

        if (!$taxable->getPrice()) {
            return;
        }

        $address = $taxable->getDestination();
        if (!$address) {
            return;
        }

        $taxRules = $this->matcher->match($address);
        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $result = $event->getResult();
        $this->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
        $this->resolveRowTotal($result, $taxRules, $taxableAmount);
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    protected function resolveUnitPrice(Result $result, array $taxRules, BigDecimal $taxableAmount)
    {
        $taxRate = BigDecimal::zero();

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $result->offsetSet(Result::UNIT, $this->calculator->calculate($taxableAmount, $taxRate));
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    protected function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount)
    {
        $taxRate = BigDecimal::zero();

        $taxResults = [];

        if ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $taxableAmount = $taxableAmount->toScale(TaxCalculatorInterface::SCALE, RoundingMode::UP);
        }

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

        $result->offsetSet(Result::ROW, $this->calculator->calculate($taxableAmount, $taxRate));
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
