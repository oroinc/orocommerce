<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Calculates taxes for row total.
 */
class RowTotalResolver
{
    use TaxCalculateResolverTrait;

    public function __construct(
        private TaxationSettingsProvider $settingsProvider,
        private TaxCalculatorInterface $calculator,
        private RoundingResolver $roundingResolver
    ) {
    }

    /**
     * @param TaxRule[] $taxRules
     */
    public function resolveRowTotal(
        Result $result,
        array $taxRules,
        BigDecimal $taxableAmount,
        BigDecimal $quantity
    ): void {
        $totalTaxRate = BigDecimal::zero();
        foreach ($taxRules as $taxRule) {
            $totalTaxRate = $totalTaxRate->plus($taxRule->getTax()->getRate());
        }

        $totalResultElement = $this->getRowTotalResult($taxableAmount, $totalTaxRate, $quantity);

        $taxResults = [];
        foreach ($taxRules as $taxRule) {
            $currentTaxRate = BigDecimal::of($taxRule->getTax()->getRate());

            if (BigDecimal::zero()->isEqualTo($totalTaxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE))) {
                $currentTaxAmount = BigDecimal::zero();
            } else {
                // Gets tax amount of current tax rule from total tax amount using proportion to avoid possible
                // tolerance errors when getting each tax amount separately, as (pseudocode):
                // sum((taxableAmount * currentTaxRate[N]).round(2))!=(taxableAmount * sum(currentTaxRate[N])).round(2)
                $currentTaxAmount = BigDecimal::of($totalResultElement->getTaxAmount())
                    ->multipliedBy($currentTaxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE))
                    ->dividedBy(
                        $totalTaxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE),
                        TaxationSettingsProvider::CALCULATION_SCALE,
                        RoundingMode::HALF_UP
                    );
            }

            $taxResults = $this->mergeTaxResult(
                $taxResults,
                $taxRule->getTax(),
                $currentTaxRate,
                BigDecimal::of($totalResultElement->getExcludingTax()),
                $currentTaxAmount
            );
        }
        if ($this->settingsProvider->isCalculateAfterPromotionsEnabled()) {
            $totalResultElement->setDiscountsIncluded(true);
        }

        $result->offsetSet(Result::ROW, $totalResultElement);
        $result->offsetSet(Result::TAXES, array_values($taxResults));
    }

    private function getRowTotalResult(
        BigDecimal $taxableAmount,
        BigDecimal $taxRate,
        BigDecimal $quantity
    ): ResultElement {
        if ($this->settingsProvider->isStartCalculationWithUnitPrice()) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
            $taxableAmount = BigDecimal::of($resultElement->getOffset($this->calculator->getAmountKey()))
                ->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP)
                ->multipliedBy($quantity);
        } elseif ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $taxableAmount = $taxableAmount
                ->multipliedBy($quantity)
                ->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
        }

        $resultElementStartWith = $this->calculator->calculate($taxableAmount, $taxRate);
        $this->calculateAdjustment($resultElementStartWith);

        return $resultElementStartWith;
    }

    private function mergeTaxResult(
        array $taxResults,
        string $taxCode,
        BigDecimal $rate,
        BigDecimal $taxableAmount,
        BigDecimal $taxAmount,
    ): array {
        if (array_key_exists($taxCode, $taxResults)) {
            $tax = $taxResults[$taxCode];
            $taxAmount = BigDecimal::of($tax->getTaxAmount())->plus($taxAmount);
            $taxableAmount = BigDecimal::of($tax->getTaxableAmount())->plus($taxableAmount);
        }

        $taxResult = TaxResultElement::create(
            $taxCode,
            $rate,
            $taxableAmount,
            $taxAmount
        );

        if ($this->settingsProvider->isStartCalculationOnItem()) {
            $this->roundingResolver->round($taxResult);
        }

        $taxResults[$taxCode] = $taxResult;

        return $taxResults;
    }
}
