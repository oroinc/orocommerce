<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class RowTotalResolver extends AbstractUnitRowResolver
{
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
            $taxResults[] = TaxResultElement::create(
                $taxRule->getTax(),
                $currentTaxRate,
                $resultElementStartWith->getExcludingTax(),
                BigDecimal::of($resultElementStartWith->getTaxAmount())
                    ->multipliedBy($currentTaxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE))
                    ->dividedBy(
                        $taxRate->toScale(TaxationSettingsProvider::CALCULATION_SCALE),
                        TaxationSettingsProvider::CALCULATION_SCALE,
                        RoundingMode::HALF_UP
                    )
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
