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
 * Calculates taxes for row total
 */
class RowTotalResolver
{
    use CalculateAdjustmentTrait;

    /**
     * @var TaxationSettingsProvider
     */
    protected $settingsProvider;

    /**
     * @var TaxCalculatorInterface
     */
    protected $calculator;

    /** @var RoundingResolver */
    protected $roundingResolver;

    public function __construct(
        TaxationSettingsProvider $settingsProvider,
        TaxCalculatorInterface $calculator,
        RoundingResolver $roundingResolver
    ) {
        $this->settingsProvider = $settingsProvider;
        $this->calculator = $calculator;
        $this->roundingResolver = $roundingResolver;
    }

    /**
     * @param Result     $result
     * @param TaxRule[]  $taxRules
     * @param BigDecimal $taxableAmount
     * @param int        $quantity
     */
    public function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount, $quantity = 1)
    {
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

            $taxResult = TaxResultElement::create(
                $taxRule->getTax(),
                $currentTaxRate,
                $totalResultElement->getExcludingTax(),
                $currentTaxAmount
            );

            if ($this->settingsProvider->isStartCalculationOnItem()) {
                $this->roundingResolver->round($taxResult);
            }

            $taxResults[] = $taxResult;
        }
        if ($this->settingsProvider->isCalculateAfterPromotionsEnabled()) {
            $totalResultElement->setDiscountsIncluded(true);
        }

        $result->offsetSet(Result::ROW, $totalResultElement);
        $result->offsetSet(Result::TAXES, $taxResults);
    }

    /**
     * @param BigDecimal $taxableAmount
     * @param BigDecimal $taxRate
     * @param int        $quantity
     * @return ResultElement
     */
    protected function getRowTotalResult(BigDecimal $taxableAmount, BigDecimal $taxRate, $quantity = 1)
    {
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
}
