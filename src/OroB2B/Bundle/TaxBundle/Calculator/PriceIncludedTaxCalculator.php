<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

class PriceIncludedTaxCalculator extends AbstractCalculator implements CalculatorInterface
{
    /** {@inheritdoc} */
    public function calculate(Taxable $taxable, Collection $taxRules)
    {
        $inclTax = $taxable->getPrice();

        $taxRate = $this->getTaxRate($taxRules);

        $taxAmount = ($inclTax * $taxRate) / (1 + $taxRate);
        $taxAmountRounded = $this->roundingService->round($taxAmount);

        $exclTax = $inclTax - $taxAmount;

        $adjustment = abs($taxAmount - $taxAmountRounded);

        return ResultElement::create(
            $this->roundingService->round($inclTax),
            $this->roundingService->round($exclTax, TaxRoundingService::TAX_PRECISION, TaxRoundingService::HALF_DOWN),
            $taxAmountRounded,
            $this->roundingService->round($adjustment, self::ADJUSTMENT_SCALE)
        );
    }
}
