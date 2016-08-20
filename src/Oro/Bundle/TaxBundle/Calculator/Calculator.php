<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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
     * {@inheritdoc}
     */
    public function calculate($amount, $taxRate)
    {
        if ($this->settingsProvider->isProductPricesIncludeTax()) {
            return $this->includedTaxCalculator->calculate($amount, $taxRate);
        }

        return $this->taxCalculator->calculate($amount, $taxRate);
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        if ($this->settingsProvider->isProductPricesIncludeTax()) {
            return $this->includedTaxCalculator->getAmountKey();
        }

        return $this->taxCalculator->getAmountKey();
    }
}
