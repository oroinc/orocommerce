<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class Calculator implements TaxCalculatorInterface
{
    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /** @var TaxCalculatorInterface */
    protected $includedTaxCalculator;

    /** @var TaxCalculatorInterface */
    protected $taxCalculator;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param TaxCalculatorInterface $includedTaxCalculator
     * @param TaxCalculatorInterface $taxCalculator
     */
    public function __construct(
        TaxationSettingsProvider $settingsProvider,
        TaxCalculatorInterface $includedTaxCalculator,
        TaxCalculatorInterface $taxCalculator
    ) {
        $this->settingsProvider = $settingsProvider;
        $this->includedTaxCalculator = $includedTaxCalculator;
        $this->taxCalculator = $taxCalculator;
    }

    /**
     * @param string $amount
     * @param TaxRule $taxRule
     * @return ResultElement
     */
    public function calculate($amount, TaxRule $taxRule)
    {
        if ($this->settingsProvider->isProductPricesIncludeTax()) {
            return $this->includedTaxCalculator->calculate($amount, $taxRule);
        }

        return $this->taxCalculator->calculate($amount, $taxRule);
    }
}
