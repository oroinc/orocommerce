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

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param TaxCalculatorInterface   $calculator
     */
    public function __construct(TaxationSettingsProvider $settingsProvider, TaxCalculatorInterface $calculator)
    {
        $this->settingsProvider = $settingsProvider;
        $this->calculator = $calculator;
    }

    /**
     * @param Result     $result
     * @param TaxRule[]  $taxRules
     * @param BigDecimal $taxableAmount
     * @param int        $quantity
     */
    public function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount, $quantity = 1)
    {
        $taxRate = BigDecimal::zero();

        $taxResults = [];

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $resultElementStartWith = $this->getRowTotalResult($taxableAmount, $taxRate, $quantity);

        foreach ($taxRules as $taxRule) {
            $currentTaxRate = BigDecimal::of($taxRule->getTax()->getRate());
            
            if (BigDecimal::zero()->isEqualTo($currentTaxRate) || BigDecimal::zero()->isEqualTo($resultElementStartWith)) {
                $taxAmount = BigDecimal::zero();
            } else {
                $taxAmount = BigDecimal::of($resultElementStartWith->getTaxAmount())
                    ->multipliedBy($currentTaxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE))
                    ->dividedBy(
                        $taxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE),
                        TaxationSettingsProvider::CALCULATION_SCALE,
                        RoundingMode::HALF_UP
                    );
            }

            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax(),
                $currentTaxRate,
                $resultElementStartWith->getExcludingTax(),
                $taxAmount
            );
        }

        $result->offsetSet(Result::ROW, $resultElementStartWith);
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
