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
        foreach ($taxable->getItems() as $taxableItem) {
            if (!$taxableItem->getPrice()) {
                continue;
            }

            $address = $taxableItem->getDestination();
            if (!$address) {
                continue;
            }

            $taxRules = $this->matcher->match($address);
            $taxableUnitPrice = BigDecimal::of($taxableItem->getPrice());
            $taxableAmount = $taxableUnitPrice->multipliedBy($taxableItem->getQuantity());

            $result = $taxableItem->getResult();
            $this->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
            $this->resolveRowTotal($result, $taxRules, $taxableAmount);
        }
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
                (string)$taxRule->getTax(),
                $currentTaxRate,
                $resultElement->getExcludingTax(),
                $resultElement->getTaxAmount()
            );
        }

        $result->offsetSet(Result::ROW, $this->calculator->calculate($taxableAmount, $taxRate));
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
