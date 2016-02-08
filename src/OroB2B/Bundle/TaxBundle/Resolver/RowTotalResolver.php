<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class RowTotalResolver
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
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    public function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount)
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
