<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Calculates the product price with/without the tax.
 */
class Calculator implements TaxCalculatorInterface
{
    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /** @var TaxCalculatorInterface */
    protected $includedTaxCalculator;

    /** @var TaxCalculatorInterface */
    protected $taxCalculator;

    /** @var bool */
    protected $isProductPricesIncludeTax;

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
        return $this->isProductPricesIncludeTax()
            ? $this->includedTaxCalculator->calculate($amount, $taxRate)
            : $this->taxCalculator->calculate($amount, $taxRate);
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        return $this->isProductPricesIncludeTax()
            ? $this->includedTaxCalculator->getAmountKey()
            : $this->taxCalculator->getAmountKey();
    }

    protected function isProductPricesIncludeTax(): bool
    {
        if (null === $this->isProductPricesIncludeTax) {
            $this->isProductPricesIncludeTax = (bool) $this->settingsProvider->isProductPricesIncludeTax();
        }

        return $this->isProductPricesIncludeTax;
    }
}
